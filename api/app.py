from flask import Flask, jsonify, request
from flask_cors import CORS
import mysql.connector
from contextlib import closing
import pandas as pd
from mlxtend.frequent_patterns import apriori, association_rules
from sklearn.metrics.pairwise import cosine_similarity

app = Flask(__name__)
CORS(app)


# =========================================
# KẾT NỐI DATABASE
# =========================================
def get_db_connection():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="flowershopdb"
    )


# =========================================
# 1) API TỔNG QUAN
# =========================================
@app.route('/api/overview')
def overview():
    try:
        with closing(get_db_connection()) as conn, closing(conn.cursor(dictionary=True)) as cur:

            cur.execute("""
                SELECT 
                    SUM(total_amount) AS doanh_thu,
                    COUNT(order_id) AS don_hang
                FROM orders
            """)
            info = cur.fetchone() or {}

            cur.execute("SELECT COUNT(user_id) AS nguoi_dung FROM users")
            users = cur.fetchone() or {}

        return jsonify({
            "doanh_thu": float(info.get("doanh_thu") or 0),
            "don_hang": int(info.get("don_hang") or 0),
            "nguoi_dung": int(users.get("nguoi_dung") or 0)
        })

    except Exception as e:
        return jsonify({"error": str(e)}), 500


# =========================================
# DOANH THU THEO THÁNG
# =========================================
@app.route('/api/doanhthu')
def doanhthu():
    try:
        month = request.args.get("month")

        if not month:
            month = "MONTH(CURRENT_DATE())"
        else:
            month = int(month)

        with closing(get_db_connection()) as conn, closing(conn.cursor(dictionary=True)) as cur:
            cur.execute(f"""
                SELECT 
                    DAY(order_date) AS ngay,
                    SUM(total_amount) AS doanh_thu
                FROM orders
                WHERE MONTH(order_date) = {month}
                  AND YEAR(order_date) = YEAR(CURRENT_DATE())
                GROUP BY DAY(order_date)
                ORDER BY ngay
            """)
            rows = cur.fetchall()

        return jsonify({
            "day": [r["ngay"] for r in rows],
            "revenue": [float(r["doanh_thu"]) for r in rows]
        })

    except Exception as e:
        return jsonify({"error": str(e)}), 500


# =========================================
# 3) TỶ LỆ ĐƠN HÀNG
# =========================================
@app.route('/api/tyle')
def tyle():
    try:
        month = request.args.get("month")

        if not month:
            month = "MONTH(CURRENT_DATE())"
        else:
            month = int(month)

        with closing(get_db_connection()) as conn, closing(conn.cursor(dictionary=True)) as cur:

            cur.execute(f"""
                SELECT
                    SUM(CASE WHEN status = 'Đã giao' THEN 1 ELSE 0 END) AS hoan_thanh,
                    SUM(CASE WHEN status = 'Đang giao hàng' THEN 1 ELSE 0 END) AS dang_giao,
                    SUM(CASE WHEN status = 'Đã hủy' THEN 1 ELSE 0 END) AS huy
                FROM orders
                WHERE MONTH(order_date) = {month}
                  AND YEAR(order_date) = YEAR(CURRENT_DATE())
            """)

            data = cur.fetchone() or {}

        return jsonify({
            "hoan_thanh": int(data.get("hoan_thanh") or 0),
            "dang_giao": int(data.get("dang_giao") or 0),
            "huy": int(data.get("huy") or 0)
        })

    except Exception as e:
        return jsonify({"error": str(e)}), 500


# =========================================
# 4) ĐƠN HÀNG GẦN ĐÂY
# =========================================
@app.route('/api/donhang')
def donhang():
    try:
        with closing(get_db_connection()) as conn, closing(conn.cursor(dictionary=True)) as cur:
            cur.execute("""
                SELECT order_id, user_id, total_amount, status
                FROM orders
                ORDER BY order_date DESC
                LIMIT 5
            """)
            rows = cur.fetchall()

        return jsonify([
            {
                "ma_don": r["order_id"],
                "ma_kh": r["user_id"],
                "tong_tien": float(r["total_amount"]),
                "trang_thai": r["status"]
            }
            for r in rows
        ])

    except Exception as e:
        return jsonify({"error": str(e)}), 500


# =========================================
# 5) NGƯỜI DÙNG MỚI
# =========================================
@app.route('/api/nguoidung')
def nguoidung():
    try:
        with closing(get_db_connection()) as conn, closing(conn.cursor(dictionary=True)) as cur:
            cur.execute("""
                SELECT 
                    username, 
                    email,
                    DATE_FORMAT(created_at, '%d/%m/%Y') AS ngay_tao
                FROM users
                ORDER BY created_at DESC
                LIMIT 5
            """)
            rows = cur.fetchall()

        return jsonify(rows)

    except Exception as e:
        return jsonify({"error": str(e)}), 500



# =========================================
#  BUILD SIMILARITY CACHE
# =========================================
product_similarity_cache = None
product_index_list = None

def build_similarity_matrix():
    global product_similarity_cache, product_index_list

    with closing(get_db_connection()) as conn:
        df = pd.read_sql("SELECT order_id, product_id FROM order_details", conn)

    if df.empty:
        print("⚠ Không có dữ liệu order_details")
        return

    basket = df.pivot_table(
        index="order_id",
        columns="product_id",
        aggfunc=len,
        fill_value=0
    ).applymap(lambda x: 1 if x > 0 else 0)

    print("✔ Pivot done:", basket.shape)

    product_index_list = basket.columns.tolist()

    sim = cosine_similarity(basket.T)

    product_similarity_cache = pd.DataFrame(
        sim,
        index=product_index_list,
        columns=product_index_list
    )

    print("✔ Similarity matrix built:", product_similarity_cache.shape)


def init_model():
    print("🔄 Building product similarity model (string IDs)...")
    build_similarity_matrix()
    print("⭐ Model ready!")


# =========================================
#  HÀM RECOMMEND
# =========================================
def recommend_product(product_id, top_n=4):
    if product_similarity_cache is None:
        return []

    if product_id not in product_similarity_cache.index:
        return []

    sim_scores = product_similarity_cache.loc[product_id]
    sim_scores = sim_scores.drop(product_id).sort_values(ascending=False)

    return sim_scores.head(top_n).index.tolist()


# =========================================
#  API FLASK
# =========================================
@app.route("/api/recommend")
def api_recommend():
    product_id = request.args.get("product_id")

    if not product_id:
        return jsonify({"error": "Missing product_id"}), 400

    top_ids = recommend_product(product_id, 4)

    if not top_ids:
        return jsonify([])

    ids_str = ",".join(f"'{pid}'" for pid in top_ids)

    with closing(get_db_connection()) as conn:
        sql = f"SELECT * FROM products WHERE product_id IN ({ids_str})"
        df = pd.read_sql(sql, conn)

    return jsonify(df.to_dict(orient="records"))


# =========================================
#  START SERVER
# =========================================
if __name__ == "__main__":
    # Build model BEFORE running Flask (fix cho Flask 3.x)
    init_model()

    app.run(host="0.0.0.0", port=5000, debug=True)
