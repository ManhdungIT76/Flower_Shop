# test_user_cf_standalone.py
# Chạy độc lập – KHÔNG import app.py, KHÔNG cần chạy Flask
# Tự kết nối DB, build user-item CF, test theo user_id truyền vào

import mysql.connector
import pandas as pd
from contextlib import closing
from sklearn.metrics.pairwise import cosine_similarity

# =========================
# CONFIG
# =========================
DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "flowershopdb"
}

TEST_USER_ID = "KH135"   # <-- đổi user_id cần test
TOP_N = 10              # số gợi ý muốn xem


# =========================
# DB CONNECTION
# =========================
def get_db_connection():
    return mysql.connector.connect(**DB_CONFIG)


# =========================
# BUILD USER-ITEM MATRIX
# =========================
def build_user_cf():
    with closing(get_db_connection()) as conn:
        df = pd.read_sql("""
            SELECT o.user_id, od.product_id, od.quantity
            FROM orders o
            JOIN order_details od ON o.order_id = od.order_id
        """, conn)

    if df.empty:
        print("❌ Không có dữ liệu orders / order_details")
        return None, None

    user_item_matrix = df.pivot_table(
        index="user_id",
        columns="product_id",
        values="quantity",
        aggfunc="sum",
        fill_value=0
    )

    sim = cosine_similarity(user_item_matrix.values)

    user_similarity = pd.DataFrame(
        sim,
        index=user_item_matrix.index,
        columns=user_item_matrix.index
    )

    return user_item_matrix, user_similarity


# =========================
# USER-ITEM CF
# =========================
def recommend_user_cf(user_id, user_item_matrix, user_similarity, top_n=10):
    if user_id not in user_item_matrix.index:
        print("❌ User không tồn tại trong user_item_matrix")
        return []

    sim_vec = user_similarity.loc[user_id].drop(user_id, errors="ignore")

    scores = user_item_matrix.T.dot(sim_vec)

    bought = user_item_matrix.loc[user_id]
    bought_items = set(bought[bought > 0].index)

    scores = scores.drop(labels=list(bought_items), errors="ignore")

    return scores.sort_values(ascending=False).head(top_n)


# =========================
# MAIN
# =========================
if __name__ == "__main__":
    print("🔄 Build user-item CF (standalone)...")

    uim, usim = build_user_cf()

    if uim is None or usim is None:
        raise SystemExit(1)

    print("✔ USER-ITEM MATRIX:", uim.shape)
    print("✔ USER-SIM MATRIX :", usim.shape)

    print("\n=== TEST USER ===")
    print("User ID:", TEST_USER_ID)

    recs = recommend_user_cf(TEST_USER_ID, uim, usim, TOP_N)

    print("Số gợi ý:", len(recs))

    if len(recs) == 0:
        print("⚠ Không có gợi ý → kiểm tra similarity hoặc lịch sử mua")
        sim_vec = usim.loc[TEST_USER_ID].drop(TEST_USER_ID, errors="ignore")
        print("Số user similarity > 0:", int((sim_vec > 0).sum()))
    else:
        print("\nDanh sách product_id + score:")
        for i, (pid, score) in enumerate(recs.items(), 1):
            print(f"{i}. {pid} | score = {round(float(score), 4)}")
