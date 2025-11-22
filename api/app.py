from flask import Flask, jsonify, request
from flask_cors import CORS
import mysql.connector
from contextlib import closing

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

            # Tổng doanh thu và đơn hàng
            cur.execute("""
                SELECT 
                    SUM(total_amount) AS doanh_thu,
                    COUNT(order_id) AS don_hang
                FROM orders
            """)
            info = cur.fetchone() or {}

            # Tổng người dùng
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
# 2) DOANH THU THEO THÁNG (CHỈ ĐÃ GIAO)
# =========================================
@app.route('/api/doanhthu')
def doanhthu():
    try:
        month = request.args.get("month")

        # Nếu không có month → dùng tháng hiện tại
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
        with closing(get_db_connection()) as conn, closing(conn.cursor(dictionary=True)) as cur:
            cur.execute("""
                SELECT
                    SUM(CASE WHEN status = 'Đã giao' THEN 1 ELSE 0 END) AS hoan_thanh,
                    SUM(CASE WHEN status = 'Đang giao hàng' THEN 1 ELSE 0 END) AS dang_giao,
                    SUM(CASE WHEN status = 'Đã hủy' THEN 1 ELSE 0 END) AS huy
                FROM orders
            """)
            data = cur.fetchone() or {}

        return jsonify({
            "hoan_thanh": int(data["hoan_thanh"]),
            "dang_giao": int(data["dang_giao"]),
            "huy": int(data["huy"])
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
# 6) GỢI Ý SẢN PHẨM - APRIORI REALTIME
# =========================================
@app.route('/api/recommend')
def recommend():
    product_id = request.args.get("product_id")
    if not product_id:
        return jsonify([])

    import pandas as pd
    from mlxtend.frequent_patterns import apriori, association_rules

    with closing(get_db_connection()) as conn:
        df = pd.read_sql("SELECT order_id, product_id FROM order_details", conn)

    if df.empty:
        return jsonify([])

    # tạo transaction matrix 0/1
    basket = (
        df.assign(count=1)
        .pivot_table(index="order_id", columns="product_id", values="count", fill_value=0)
    )
    basket = basket.applymap(lambda x: 1 if x > 0 else 0)

    # chạy apriori
    frequent_items = apriori(basket, min_support=0.01, use_colnames=True)
    if frequent_items.empty:
        return jsonify([])

    rules = association_rules(frequent_items, metric="confidence", min_threshold=0.1)
    if rules.empty:
        return jsonify([])

    # lọc luật liên quan đến sản phẩm A
    rec = rules[
        rules['antecedents'].apply(lambda x: len(x)==1 and list(x)[0]==product_id)
    ]

    # lọc thêm lift
    rec = rec[rec["lift"] >= 1.2]

    if rec.empty:
        return jsonify([])

    # lấy top 4 theo confidence
    rec = rec.sort_values("confidence", ascending=False).head(4)

    recommends = []
    for _, row in rec.iterrows():
        recommends.append({
            "product_id": list(row["consequents"])[0],
            "confidence": float(row["confidence"]),
            "lift": float(row["lift"])
        })

    return jsonify(recommends)

# =========================================
# START SERVER
# =========================================
if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)
