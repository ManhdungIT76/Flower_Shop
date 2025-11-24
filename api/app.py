from flask import Flask, jsonify, request
from flask_cors import CORS
import mysql.connector
from contextlib import closing
import pandas as pd
from mlxtend.frequent_patterns import apriori, association_rules
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



# ===========================================
# CACHE APRIORI CATEGORY
# ===========================================
apriori_cache = {
    "rules": None,
    "last_count": 0
}

# ===========================================
# HÀM: LẤY TOP SẢN PHẨM BÁN CHẠY TRONG DANH MỤC
# ===========================================
def get_top_selling(category_id, limit=4):
    with closing(get_db_connection()) as conn:
        sql = """
            SELECT od.product_id, p.product_name, p.price, COUNT(*) AS cnt
            FROM order_details od
            JOIN products p ON p.product_id = od.product_id
            WHERE p.category_id = %s
            GROUP BY od.product_id, p.product_name, p.price
            ORDER BY cnt DESC
            LIMIT %s;
        """
        cur = conn.cursor(dictionary=True)
        cur.execute(sql, (category_id, limit))
        return cur.fetchall()

# ===========================================
# HÀM: LẤY RANDOM SẢN PHẨM TRONG DANH MỤC
# ===========================================
def get_random_products(category_id, limit):
    with closing(get_db_connection()) as conn:
        sql = """
            SELECT product_id, product_name, price
            FROM products
            WHERE category_id = %s
            ORDER BY RAND()
            LIMIT %s
        """
        cur = conn.cursor(dictionary=True)
        cur.execute(sql, (category_id, limit))
        return cur.fetchall()

# ===========================================
# API RECOMMEND (CATEGORY → CATEGORY → PRODUCT)
# ===========================================
@app.route('/api/recommend')
def recommend():
    product_id = request.args.get("product_id")
    if not product_id:
        return jsonify([])

    # ===========================================
    # 1) LOAD order_details
    # ===========================================
    with closing(get_db_connection()) as conn:
        df = pd.read_sql("SELECT order_id, product_id FROM order_details", conn)

    if df.empty:
        return jsonify([])

    # ===========================================
    # 2) LOAD products → tìm category của sản phẩm
    # ===========================================
    with closing(get_db_connection()) as conn:
        cur = conn.cursor(dictionary=True)
        cur.execute("SELECT category_id FROM products WHERE product_id=%s", (product_id,))
        row = cur.fetchone()

    if not row:
        return jsonify([])

    source_category = row["category_id"]

    # ===========================================
    # 3) TẠO BASKET THEO DANH MỤC
    # ===========================================
    with closing(get_db_connection()) as conn:
        df_prod = pd.read_sql("SELECT product_id, category_id FROM products", conn)

    df = df.merge(df_prod, on="product_id")
    df["count"] = 1

    basket = df.pivot_table(
        index="order_id",
        columns="category_id",
        values="count",
        aggfunc="sum",
        fill_value=0
    ).applymap(lambda x: 1 if x > 0 else 0)

    # ===========================================
    # 4) APRIORI CATEGORY (CÓ CACHE)
    # ===========================================
    row_count = len(df)

    if apriori_cache["rules"] is not None and apriori_cache["last_count"] == row_count:
        rules_cat = apriori_cache["rules"]
    else:
        freq = apriori(basket, min_support=0.003, use_colnames=True)
        rules_cat = association_rules(freq, metric="confidence", min_threshold=0.3)

        rules_cat = rules_cat[
            rules_cat["antecedents"].apply(lambda x: len(x) == 1) &
            rules_cat["consequents"].apply(lambda x: len(x) == 1) &
            (rules_cat["lift"] >= 1.1)
        ]

        apriori_cache["rules"] = rules_cat
        apriori_cache["last_count"] = row_count

    # ===========================================
    # 5) LỌC LUẬT TỪ DANH MỤC NGUỒN
    # ===========================================
    matches = rules_cat[
        rules_cat["antecedents"].apply(lambda x: list(x)[0] == source_category)
    ]

    target_categories = [list(row["consequents"])[0] for _, row in matches.iterrows()]

    # ===========================================
    # KHỞI TẠO LIST GỢI Ý (CHỐNG TRÙNG)
    # ===========================================
    suggestions = []
    used_product_ids = set()   # chứa product_id đã được thêm

    def add_unique(products):
        """Thêm SP vào gợi ý, không cho trùng"""
        nonlocal suggestions, used_product_ids
        for p in products:
            if p["product_id"] not in used_product_ids:
                suggestions.append(p)
                used_product_ids.add(p["product_id"])
                if len(suggestions) >= 4:
                    break

    # ===========================================
    # 6) KHÔNG CÓ LUẬT → top 4 cùng danh mục
    # ===========================================
    if len(target_categories) == 0:
        top = get_top_selling(source_category, 8)
        add_unique(top)

        if len(suggestions) < 4:
            rand = get_random_products(source_category, 8)
            add_unique(rand)

        return jsonify(suggestions[:4])

    # ===========================================
    # 7) ≥ 2 luật → mỗi luật lấy 1 SP bán chạy (CHỐNG TRÙNG)
    # ===========================================
    for tcat in target_categories:
        top1 = get_top_selling(tcat, 3)  # lấy 3 để phòng trùng
        add_unique(top1)

        if len(suggestions) >= 4:
            break

    # ===========================================
    # 8) Nếu vẫn chưa đủ 4 SP → lấy thêm bán chạy từ luật đầu tiên
    # ===========================================
    if len(suggestions) < 4:
        primary_target = target_categories[0]

        need_more = 4 - len(suggestions)

        top_more = get_top_selling(primary_target, need_more * 3)
        add_unique(top_more)

    # ===========================================
    # 9) Cuối cùng, nếu vẫn thiếu → random nhưng chống trùng
    # ===========================================
    if len(suggestions) < 4:
        primary_target = target_categories[0]
        need_more = 4 - len(suggestions)

        rand_more = get_random_products(primary_target, need_more * 3)
        add_unique(rand_more)

    return jsonify(suggestions[:4])


# =========================================
# START SERVER
# =========================================
if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)
