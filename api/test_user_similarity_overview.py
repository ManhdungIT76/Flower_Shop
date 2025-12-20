import pandas as pd
import mysql.connector
from contextlib import closing
from sklearn.metrics.pairwise import cosine_similarity
import numpy as np

# ===============================
# KẾT NỐI DB
# ===============================
def get_db_connection():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="flowershopdb"
    )

# ===============================
# BUILD USER-ITEM MATRIX
# ===============================
with closing(get_db_connection()) as conn:
    df = pd.read_sql("""
        SELECT o.user_id, od.product_id, od.quantity
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
    """, conn)

user_item = df.pivot_table(
    index="user_id",
    columns="product_id",
    values="quantity",
    aggfunc="sum",
    fill_value=0
)

print("✔ USER-ITEM MATRIX:", user_item.shape)

# ===============================
# BUILD USER-SIMILARITY MATRIX
# ===============================
sim = cosine_similarity(user_item.values)

user_sim = pd.DataFrame(
    sim,
    index=user_item.index,
    columns=user_item.index
)

print("✔ USER-SIM MATRIX :", user_sim.shape)

# ======================================================
# 1️⃣ PHÂN BỐ SIMILARITY TOÀN DB (KHÔNG TÍNH ĐƯỜNG CHÉO)
# ======================================================
mask = ~np.eye(len(user_sim), dtype=bool)
vals = user_sim.values[mask]

print("\n=== PHÂN BỐ SIMILARITY ===")
print(f"Min   : {vals.min():.4f}")
print(f"Mean  : {vals.mean():.4f}")
print(f"Median: {np.median(vals):.4f}")
print(f"Max   : {vals.max():.4f}")

# ======================================================
# 2️⃣ TOP 10 CẶP USER GIỐNG NHAU NHẤT
# ======================================================
pairs = []
users = user_sim.index.tolist()

for i in range(len(users)):
    for j in range(i + 1, len(users)):
        s = user_sim.iat[i, j]
        pairs.append((users[i], users[j], s))

pairs.sort(key=lambda x: x[2], reverse=True)

print("\n=== TOP 10 CẶP USER SIMILARITY CAO NHẤT ===")
for u1, u2, s in pairs[:10]:
    print(f"{u1} <-> {u2} | similarity = {s:.4f}")

# ======================================================
# 3️⃣ ĐẾM SỐ CẶP USER VƯỢT NGƯỠNG
# ======================================================
THRESHOLDS = [0.3, 0.5, 0.7]

print("\n=== SỐ CẶP USER THEO NGƯỠNG SIMILARITY ===")
for t in THRESHOLDS:
    cnt = sum(1 for _, _, s in pairs if s >= t)
    print(f">= {t}: {cnt} cặp")

# ======================================================
# 4️⃣ XEM MA TRẬN RÚT GỌN (10x10)
# ======================================================
print("\n=== USER-SIM MATRIX (10x10) ===")
print(user_sim.iloc[:10, :10].round(3))
