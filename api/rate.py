# file: eval_user_cf_hit_rate.py
# Split CÁCH A: train = 2024-2025, test = 2026
# Schema:
# - orders(order_id, user_id, order_date, ...)
# - order_details(order_detail_id, order_id, product_id, quantity, unit_price)

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

# =======================
# SPLIT CÁCH A
# =======================
TRAIN_END  = "2025-01-01 23:59:59"   # train: <= mốc này
TEST_START = "2025-02-02 00:00:00"   # test : >= mốc này

MIN_ORDERS_IN_TRAIN = 5

TOPK_NEIGHBORS = 5
SIM_THRESHOLD = 0.01

K_LIST = [5, 10]
TOPN_RECS_MAX = max(K_LIST)

def get_conn():
    return mysql.connector.connect(**DB_CONFIG)

def load_train_interactions(conn) -> pd.DataFrame:
    q = """
        SELECT o.user_id, od.product_id, od.quantity
        FROM orders o
        JOIN order_details od ON od.order_id = o.order_id
        WHERE o.order_date <= %s
    """
    return pd.read_sql(q, conn, params=[TRAIN_END])

def load_test_purchases(conn) -> pd.DataFrame:
    q = """
        SELECT o.user_id, od.product_id
        FROM orders o
        JOIN order_details od ON od.order_id = o.order_id
        WHERE o.order_date >= %s
    """
    return pd.read_sql(q, conn, params=[TEST_START])

def load_train_order_counts(conn) -> pd.Series:
    q = """
        SELECT o.user_id, COUNT(DISTINCT o.order_id) AS total_orders
        FROM orders o
        WHERE o.order_date <= %s
        GROUP BY o.user_id
    """
    df = pd.read_sql(q, conn, params=[TRAIN_END])
    if df.empty:
        return pd.Series(dtype=int)
    return df.set_index("user_id")["total_orders"].astype(int)

def build_user_item(train_df: pd.DataFrame) -> pd.DataFrame:
    if train_df.empty:
        return pd.DataFrame()
    return train_df.pivot_table(
        index="user_id",
        columns="product_id",
        values="quantity",
        aggfunc="sum",
        fill_value=0
    )

def build_user_similarity(uim: pd.DataFrame) -> pd.DataFrame:
    if uim.empty:
        return pd.DataFrame()
    sim = cosine_similarity(uim.values)
    return pd.DataFrame(sim, index=uim.index, columns=uim.index)

def recommend_user_cf_topk(user_id: str, uim: pd.DataFrame, usim: pd.DataFrame,
                           top_k_neighbors: int, top_n: int, sim_threshold: float) -> list:
    if uim.empty or usim.empty:
        return []
    if user_id not in uim.index or user_id not in usim.index:
        return []

    sim_vec = usim.loc[user_id].drop(index=user_id, errors="ignore").copy()
    if sim_threshold > 0:
        sim_vec = sim_vec[sim_vec >= sim_threshold]
    if sim_vec.empty:
        return []

    sim_top = sim_vec.sort_values(ascending=False).head(top_k_neighbors)
    if sim_top.empty:
        return []

    neighbor_uim = uim.loc[sim_top.index]

    # score(item) = Σ sim(u,v) * quantity(v,item)
    scores_arr = neighbor_uim.T.values @ sim_top.values
    scores = pd.Series(scores_arr, index=neighbor_uim.columns)

    # normalize
    den = float(np.abs(sim_top.values).sum())
    if den > 0:
        scores = scores / den

    # loại bỏ sản phẩm user đã mua trong TRAIN
    already_bought = uim.loc[user_id][uim.loc[user_id] > 0].index
    scores = scores.drop(index=already_bought, errors="ignore")

    scores = scores[scores > 0]
    return scores.sort_values(ascending=False).head(top_n).index.tolist()

def hit_rate_at_k(recs: dict, actuals: dict, k: int) -> float:
    hits = 0
    users = 0
    for u, rec_list in recs.items():
        act = actuals.get(u, set())
        if not act:
            continue
        users += 1
        if set(rec_list[:k]) & act:
            hits += 1
    return hits / users if users > 0 else 0.0

def main():
    conn = get_conn()

    train_df = load_train_interactions(conn)
    test_df = load_test_purchases(conn)
    train_orders = load_train_order_counts(conn)

    if train_df.empty:
        print("Train rỗng: không đủ dữ liệu để build CF.")
        conn.close()
        return
    if test_df.empty:
        print("Test rỗng: không đủ dữ liệu để đánh giá.")
        conn.close()
        return

    uim = build_user_item(train_df)
    usim = build_user_similarity(uim)

    # ground truth test: user -> set(product_id)
    actuals = (
        test_df.groupby("user_id")["product_id"]
        .apply(lambda s: set(s.astype(str).tolist()))
        .to_dict()
    )

    # eligible: có đủ đơn train và có mua trong test
    eligible = []
    for u in uim.index.astype(str).tolist():
        if int(train_orders.get(u, 0)) >= MIN_ORDERS_IN_TRAIN and len(actuals.get(u, set())) > 0:
            eligible.append(u)

    print("=== SPLIT (CÁCH A) ===")
    print("TRAIN_END :", TRAIN_END)
    print("TEST_START:", TEST_START)
    print("\n=== DATA SUMMARY ===")
    print("Train rows:", len(train_df), "| Users:", uim.shape[0], "| Items:", uim.shape[1])
    print("Test rows :", len(test_df), "| Users with test purchases:", len(actuals))
    print("Eligible users (train_orders>=%d & has_test): %d" % (MIN_ORDERS_IN_TRAIN, len(eligible)))

    if not eligible:
        print("Không có user đủ điều kiện để đánh giá theo tiêu chí hiện tại.")
        conn.close()
        return

    # generate recs from TRAIN only
    recs = {}
    for u in eligible:
        recs[u] = recommend_user_cf_topk(
            u, uim, usim,
            top_k_neighbors=TOPK_NEIGHBORS,
            top_n=TOPN_RECS_MAX,
            sim_threshold=SIM_THRESHOLD
        )

    print("\n=== HIT RATE RESULTS ===")
    for k in K_LIST:
        hr = hit_rate_at_k(recs, actuals, k)
        print(f"HitRate@{k}: {hr:.4f}")

    # sample debug
    print("\n=== SAMPLE (first 5 eligible users) ===")
    for u in eligible[:5]:
        print("- user:", u, "| train_orders:", int(train_orders.get(u, 0)))
        print("  test_bought_size:", len(actuals.get(u, set())))
        print("  rec_top10:", recs.get(u, [])[:10])

    conn.close()

if __name__ == "__main__":
    main()
