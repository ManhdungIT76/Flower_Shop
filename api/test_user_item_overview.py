# file: test_user_item_overview.py
# Mục tiêu:
# - Tự kết nối MySQL
# - Build USER-ITEM matrix (quantity)
# - Build USER-USER similarity (cosine)
# - Liệt kê user đủ điều kiện (>=5 hóa đơn)
# - Hiển thị TOP 2 user gần nhất (similarity cao nhất)
# - Gợi ý sản phẩm dựa trên TOP 2 neighbors
# - Không cần chạy Flask / app.py

import mysql.connector
import pandas as pd
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity

DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "flowershopdb",
}

MIN_ORDERS = 5
TOP_RECS = 10
EXPORT_CSV = False

TOPK_NEIGHBORS = 5       # ✅ chỉ hiển thị + dùng top 2 gần nhất
SIM_THRESHOLD = 0.01     # ✅ ngưỡng similarity (để 0 nếu muốn luôn lấy top 2)

def get_conn():
    return mysql.connector.connect(**DB_CONFIG)

def load_user_item_df(conn) -> pd.DataFrame:
    df = pd.read_sql(
        """
        SELECT o.user_id, od.product_id, od.quantity
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        """,
        conn
    )
    if df.empty:
        return pd.DataFrame()

    uim = df.pivot_table(
        index="user_id",
        columns="product_id",
        values="quantity",
        aggfunc="sum",
        fill_value=0
    )
    return uim

def load_user_order_counts(conn) -> pd.Series:
    df = pd.read_sql(
        """
        SELECT user_id, COUNT(DISTINCT order_id) AS total_orders
        FROM orders
        GROUP BY user_id
        """,
        conn
    )
    if df.empty:
        return pd.Series(dtype=int)
    return df.set_index("user_id")["total_orders"].astype(int)

def build_user_similarity(uim: pd.DataFrame) -> pd.DataFrame:
    sim = cosine_similarity(uim.values)
    return pd.DataFrame(sim, index=uim.index, columns=uim.index)

def top_similar_users(user_id: str, usim: pd.DataFrame, top_k=2, sim_threshold=0.0):
    """
    Trả về list (other_user_id, similarity) theo thứ tự giảm dần.
    """
    if usim is None or usim.empty or user_id not in usim.index:
        return []

    s = usim.loc[user_id].drop(index=user_id, errors="ignore").copy()

    if sim_threshold > 0:
        s = s[s >= sim_threshold]

    if s.empty:
        return []

    top = s.sort_values(ascending=False).head(top_k)
    return list(zip(top.index.tolist(), top.values.tolist()))

def recommend_user_cf_topk(user_id: str, uim: pd.DataFrame, usim: pd.DataFrame,
                           top_k_neighbors: int = 5,
                           top_n: int = 10,
                           sim_threshold: float = 0.0):
    """
    User-based CF (Top-K neighbors):
    - Chỉ dùng top_k_neighbors user có similarity cao nhất (lọc theo sim_threshold nếu cần)
    - score(item) = Σ_{v in topK} sim(u,v) * quantity(v,item)
    - normalize theo tổng |sim| để ổn định thang điểm
    - loại bỏ item user đã mua
    """
    if uim is None or usim is None or uim.empty or usim.empty:
        return []

    if user_id not in uim.index or user_id not in usim.index:
        return []

    # similarity vector của u với các user khác
    sim_vec = usim.loc[user_id].drop(index=user_id, errors="ignore").copy()

    if sim_threshold > 0:
        sim_vec = sim_vec[sim_vec >= sim_threshold]

    if sim_vec.empty:
        return []

    # lấy top K neighbors
    sim_top = sim_vec.sort_values(ascending=False).head(top_k_neighbors)
    if sim_top.empty:
        return []

    neighbor_uim = uim.loc[sim_top.index]

    # score theo topK
    scores_arr = neighbor_uim.T.values @ sim_top.values
    scores = pd.Series(scores_arr, index=neighbor_uim.columns)

    # normalize theo tổng similarity
    den = float(np.abs(sim_top.values).sum())
    if den > 0:
        scores = scores / den

    # loại bỏ item user đã mua
    already_bought = uim.loc[user_id][uim.loc[user_id] > 0].index
    scores = scores.drop(index=already_bought, errors="ignore")

    # chỉ giữ điểm dương
    scores = scores[scores > 0]

    return scores.sort_values(ascending=False).head(top_n).index.tolist()

def get_product_info(conn, product_ids):
    if not product_ids:
        return pd.DataFrame(columns=["product_id", "product_name", "price", "category_id"])
    placeholders = ",".join(["%s"] * len(product_ids))
    cur = conn.cursor(dictionary=True)
    cur.execute(
        f"""
        SELECT product_id, product_name, price, category_id
        FROM products
        WHERE product_id IN ({placeholders})
        """,
        product_ids
    )
    rows = cur.fetchall()
    cur.close()

    if not rows:
        return pd.DataFrame(columns=["product_id", "product_name", "price", "category_id"])

    # giữ thứ tự theo product_ids
    m = {r["product_id"]: r for r in rows}
    ordered = [m[i] for i in product_ids if i in m]
    return pd.DataFrame(ordered)

def main():
    conn = get_conn()

    uim = load_user_item_df(conn)
    if uim.empty:
        print("Không có dữ liệu để build user-item matrix.")
        conn.close()
        return

    usim = build_user_similarity(uim)
    order_counts = load_user_order_counts(conn)
    eligible_users = order_counts[order_counts >= MIN_ORDERS].index.tolist()

    print("=== MATRIX SHAPES ===")
    print("USER-ITEM:", uim.shape)
    print("USER-SIM :", usim.shape)
    print()
    print(f"=== ELIGIBLE USERS (orders >= {MIN_ORDERS}) ===")
    print("count:", len(eligible_users))
    print()

    # Similarity distribution
    sim_vals = usim.values.copy()
    np.fill_diagonal(sim_vals, np.nan)
    flat = sim_vals[~np.isnan(sim_vals)].flatten()
    print("=== SIMILARITY DISTRIBUTION (excluding diagonal) ===")
    print("Min   :", float(np.min(flat)))
    print("Mean  :", float(np.mean(flat)))
    print("Median:", float(np.median(flat)))
    print("Max   :", float(np.max(flat)))
    print()

    all_export_rows = []

    for u in eligible_users:
        print("=" * 60)
        print("USER:", u, "| orders:", int(order_counts.get(u, 0)))

        # ✅ chỉ hiển thị TOP 2 neighbors
        neigh2 = top_similar_users(u, usim, top_k=TOPK_NEIGHBORS, sim_threshold=SIM_THRESHOLD)
        print(f"- Top {TOPK_NEIGHBORS} similar users (thr={SIM_THRESHOLD}):")
        if not neigh2:
            print("  (Không có neighbor đạt ngưỡng similarity)")
        else:
            for v, s in neigh2:
                print(f"  {u} <-> {v} | sim={s:.4f}")

        # recommendations dựa trên top 2
        rec_ids = recommend_user_cf_topk(
            u, uim, usim,
            top_k_neighbors=TOPK_NEIGHBORS,
            top_n=TOP_RECS,
            sim_threshold=SIM_THRESHOLD
        )
        print(f"- Recommendations (top {TOP_RECS}):", len(rec_ids))

        if not rec_ids:
            print("  (Không có sản phẩm gợi ý: similarity thấp hoặc user đã mua gần hết)")
            continue

        rec_df = get_product_info(conn, rec_ids)
        if rec_df.empty:
            print("  (Không lấy được thông tin sản phẩm từ bảng products)")
            continue

        for _, r in rec_df.iterrows():
            print(f"  {r['product_id']} | {r['product_name']} | {r['price']} | {r['category_id']}")

        if EXPORT_CSV:
            for pid in rec_ids:
                all_export_rows.append({"user_id": u, "recommended_product_id": pid})

    if EXPORT_CSV and all_export_rows:
        out = pd.DataFrame(all_export_rows)
        out.to_csv("user_cf_recommendations.csv", index=False, encoding="utf-8-sig")
        print("\nSaved: user_cf_recommendations.csv")

    conn.close()

if __name__ == "__main__":
    main()
