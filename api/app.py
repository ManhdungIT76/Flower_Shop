from flask import Flask, jsonify, request
from flask_cors import CORS
import mysql.connector
from contextlib import closing
import pandas as pd
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
#  BUILD ITEM-ITEM SIMILARITY CACHE
# =========================================
product_similarity_cache = None
product_index_list = None


def build_similarity_matrix():
    global product_similarity_cache, product_index_list

    with closing(get_db_connection()) as conn:
        df = pd.read_sql("SELECT order_id, product_id FROM order_details", conn)

    if df.empty:
        product_similarity_cache = None
        product_index_list = None
        return

    df["order_id"] = df["order_id"].astype(str).str.strip()
    df["product_id"] = df["product_id"].astype(str).str.strip()

    basket = df.pivot_table(
        index="order_id",
        columns="product_id",
        aggfunc=len,
        fill_value=0
    )
    # nhị phân hóa
    basket = (basket > 0).astype(int)

    product_index_list = basket.columns.tolist()

    sim = cosine_similarity(basket.T.values)

    product_similarity_cache = pd.DataFrame(
        sim,
        index=product_index_list,
        columns=product_index_list
    )


def recommend_product(product_id, top_n=10):
    if product_similarity_cache is None:
        return []
    product_id = str(product_id).strip()
    if product_id not in product_similarity_cache.index:
        return []

    sim_scores = product_similarity_cache.loc[product_id].drop(labels=[product_id], errors="ignore")
    sim_scores = sim_scores.sort_values(ascending=False)
    return sim_scores.head(top_n).index.tolist()


@app.route("/api/recommend")
def api_recommend():
    product_id = request.args.get("product_id")
    if not product_id:
        return jsonify({"error": "Missing product_id"}), 400

    top_ids = recommend_product(product_id, 10)
    if not top_ids:
        return jsonify([])

    placeholders = ",".join(["%s"] * len(top_ids))

    with closing(get_db_connection()) as conn, closing(conn.cursor(dictionary=True)) as cur:
        cur.execute(f"SELECT * FROM products WHERE product_id IN ({placeholders})", top_ids)
        rows = cur.fetchall()

    m = {r["product_id"]: r for r in rows}
    ordered = [m[i] for i in top_ids if i in m]
    return jsonify(ordered)


# =========================================
#  BUILD USER-ITEM CF CACHE (user-based)
# =========================================
user_item_matrix = None        # DataFrame: user_id x product_id (quantity)
user_similarity_cache = None   # DataFrame: user_id x user_id


def build_user_similarity_matrix():
    global user_item_matrix, user_similarity_cache

    with closing(get_db_connection()) as conn:
        df = pd.read_sql("""
            SELECT o.user_id, od.product_id, od.quantity
            FROM orders o
            JOIN order_details od ON o.order_id = od.order_id
        """, conn)

    if df.empty:
        user_item_matrix = None
        user_similarity_cache = None
        return

    df["user_id"] = df["user_id"].astype(str).str.strip()
    df["product_id"] = df["product_id"].astype(str).str.strip()

    user_item_matrix = df.pivot_table(
        index="user_id",
        columns="product_id",
        values="quantity",
        aggfunc="sum",
        fill_value=0
    )

    # (Tuỳ chọn) giảm ảnh hưởng heavy buyers:
    # user_item_matrix = (user_item_matrix > 0).astype(int)   # binary
    # hoặc:
    # user_item_matrix = np.log1p(user_item_matrix)           # cần import numpy as np

    sim = cosine_similarity(user_item_matrix.values)

    user_similarity_cache = pd.DataFrame(
        sim,
        index=user_item_matrix.index,
        columns=user_item_matrix.index
    )


def _parse_csv(s):
    if not s:
        return []
    return [x.strip() for x in s.split(",") if x.strip()]


def recommend_user_cf(user_id, top_n=10, exclude=None, top_k_neighbors=5, sim_threshold=0.0):
    """
    user-based CF (Top-K neighbors):
    - Chỉ lấy top_k_neighbors user giống nhất (mặc định 2)
    - score(item) = Σ_{v in topK} sim(u,v) * qty(v,item)
    - normalize theo tổng |sim| để ổn định
    - loại bỏ item đã mua + exclude
    - lọc score > 0
    """
    if user_item_matrix is None or user_similarity_cache is None:
        return []

    user_id = str(user_id).strip()
    if user_id not in user_item_matrix.index:
        return []

    exclude_set = set(exclude or [])

    # similarity vector (bỏ chính nó)
    sim_vec = user_similarity_cache.loc[user_id].drop(index=user_id, errors="ignore").copy()

    # align theo index user_item_matrix
    sim_vec = sim_vec.reindex(user_item_matrix.index.drop(user_id, errors="ignore")).fillna(0.0)

    # lọc theo ngưỡng nếu cần
    if sim_threshold > 0:
        sim_vec = sim_vec[sim_vec >= sim_threshold]

    if sim_vec.empty:
        return []

    # lấy TOP-K neighbors
    sim_top = sim_vec.sort_values(ascending=False).head(int(top_k_neighbors))
    if sim_top.empty:
        return []

    neighbor_uim = user_item_matrix.loc[sim_top.index]

    # tính score theo topK
    scores_arr = neighbor_uim.T.values @ sim_top.values
    scores = pd.Series(scores_arr, index=neighbor_uim.columns)

    # normalize theo tổng similarity
    den = float(abs(sim_top.values).sum())
    if den > 0:
        scores = scores / den

    # remove already bought + exclude
    user_items = user_item_matrix.loc[user_id]
    already_bought = set(user_items[user_items > 0].index.tolist())

    drop_ids = already_bought | exclude_set
    scores = scores.drop(labels=list(drop_ids), errors="ignore")

    # lọc điểm dương để tránh trả về item 0 điểm
    scores = scores[scores > 0]

    top_ids = scores.sort_values(ascending=False).head(top_n).index.tolist()
    return top_ids


@app.route("/api/recommend/user")
def api_recommend_user():
    user_id = (request.args.get("user_id") or "").strip()
    limit = request.args.get("limit", "10")
    exclude_str = (request.args.get("exclude") or "").strip()

    # NEW: cho phép chỉnh topK neighbors + threshold qua query (không bắt buộc)
    topk = request.args.get("topk", "5")
    thr = request.args.get("thr", "0")   # similarity threshold

    if not user_id:
        return jsonify({"error": "Missing user_id"}), 400

    try:
        limit = int(limit)
    except Exception:
        limit = 4

    try:
        topk = int(topk)
    except Exception:
        topk = 5

    try:
        thr = float(thr)
    except Exception:
        thr = 0.0

    exclude_ids = _parse_csv(exclude_str)

    top_ids = recommend_user_cf(
        user_id,
        top_n=limit,
        exclude=exclude_ids,
        top_k_neighbors=topk,
        sim_threshold=thr
    )
    if not top_ids:
        return jsonify([])

    placeholders = ",".join(["%s"] * len(top_ids))

    with closing(get_db_connection()) as conn, closing(conn.cursor(dictionary=True)) as cur:
        cur.execute(f"SELECT * FROM products WHERE product_id IN ({placeholders})", top_ids)
        rows = cur.fetchall()

    m = {r["product_id"]: r for r in rows}
    ordered = [m[i] for i in top_ids if i in m]
    return jsonify(ordered)

# =========================================
#  INIT MODEL
# =========================================
def init_model():
    build_similarity_matrix()
    build_user_similarity_matrix()
# =========================================
#  START SERVER
# =========================================
if __name__ == "__main__":
    init_model()
    app.run(host="0.0.0.0", port=5000, debug=True)
