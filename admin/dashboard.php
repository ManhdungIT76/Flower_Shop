<?php include 'includes/admin_header.php'; ?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang quản trị - Blossomy Bliss</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    <style>
        body { font-family:'Poppins',sans-serif; background:#fffaf8; color:#4b2e1e; margin:0; }

        .cards { display:flex; gap:20px; flex-wrap:wrap; margin-bottom:30px; }
        .card { flex:1; background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.1); padding:20px;
                min-width:260px; text-align:center; font-size:18px; font-weight:bold; }

        .chart-container { display:flex; gap:40px; flex-wrap:wrap; margin-top:20px; }
        canvas { background:#fff; border-radius:10px; padding:10px;
                 box-shadow:0 2px 6px rgba(0,0,0,0.1); }

        table { width:100%; border-collapse:collapse; background:#fff; margin-top:20px;
                border-radius:10px; overflow:hidden; }
        th, td { padding:10px 15px; border-bottom:1px solid #f0d8ce; text-align:left; }
        th { background-color:#f8eae5; font-weight:bold; }

        .status-done { color:green; font-weight:bold; }
        .status-shipping { color:#e67e22; font-weight:bold; }
        .status-cancel { color:red; font-weight:bold; }

        .status-dot { height:12px; width:12px; border-radius:50%; display:inline-block; }
        .status-green { background:#8bc34a; }
    </style>
</head>

<body>

<h1 style="margin-bottom:20px; font-size:28px; display:flex; align-items:center; gap:10px;">
    📊 Tổng quan hệ thống
</h1>

<!-- ==== 3 Ô Tổng quan ==== -->
<div class="cards">
    <div class="card" id="cardDoanhThu">Tổng doanh thu: ...</div>
    <div class="card" id="cardDonHang">Tổng đơn hàng: ...</div>
    <div class="card" id="cardNguoiDung">Tổng người dùng: ...</div>
</div>

<!-- ==== BIỂU ĐỒ ==== -->
 <div style="margin-bottom: 10px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
  <div>
    <label>Năm:</label>
    <select id="selectYear" onchange="onFilterChange()"></select>
  </div>

  <div>
    <label>Tháng:</label>
    <select id="selectMonth" onchange="onFilterChange()">
      <option value="1">Tháng 1</option>
      <option value="2">Tháng 2</option>
      <option value="3">Tháng 3</option>
      <option value="4">Tháng 4</option>
      <option value="5">Tháng 5</option>
      <option value="6">Tháng 6</option>
      <option value="7">Tháng 7</option>
      <option value="8">Tháng 8</option>
      <option value="9">Tháng 9</option>
      <option value="10">Tháng 10</option>
      <option value="11">Tháng 11</option>
      <option value="12">Tháng 12</option>
    </select>
  </div>
</div>
<div class="chart-container">
    <div style="flex:2;">
        <h3>Doanh thu theo tháng</h3>
        <canvas id="chartDoanhThu"></canvas>
    </div>

    <div style="flex:1;">
        <h3>Tỷ lệ đơn hàng</h3>
        <canvas id="chartTyLe"></canvas>
    </div>
</div>

<!-- ==== Bảng dữ liệu ==== -->
<div style="display:flex; gap:40px; margin-top:40px; flex-wrap:wrap;">

    <div style="flex:1;">
        <h3>Đơn hàng gần đây</h3>
        <table id="tableDonHang">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Mã KH</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div style="flex:1;">
        <h3>Người dùng mới</h3>
        <table id="tableNguoiDung">
            <thead>
                <tr>
                    <th>Tên đăng nhập</th>
                    <th>Email</th>
                    <th>Ngày tạo</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

<!-- ===================================================================== -->
<!--                          SCRIPT DASHBOARD                             -->
<!-- ===================================================================== -->

<script>
const API_BASE = "http://localhost:5000/api";


// ===== 1. OVERVIEW =====
async function loadOverview() {
    const res = await fetch(`${API_BASE}/overview`);
    const data = await res.json();

    document.getElementById("cardDoanhThu").innerText =
        `Tổng doanh thu: ${data.doanh_thu.toLocaleString()} đ`;

    document.getElementById("cardDonHang").innerText =
        `Tổng đơn hàng: ${data.don_hang}`;

    document.getElementById("cardNguoiDung").innerText =
        `Tổng người dùng: ${data.nguoi_dung}`;
}


// ===== API lấy danh sách năm =====
async function loadYears() {
  const res = await fetch(`${API_BASE}/years`);
  const years = await res.json(); // ví dụ: [2023, 2024, 2025]

  const yearSelect = document.getElementById("selectYear");
  yearSelect.innerHTML = "";

  years.forEach(y => {
    const opt = document.createElement("option");
    opt.value = y;
    opt.textContent = `Năm ${y}`;
    yearSelect.appendChild(opt);
  });

  // set mặc định: năm hiện tại nếu có, không có thì chọn năm đầu tiên trong list
  const currentYear = new Date().getFullYear();
  if (years.includes(currentYear)) yearSelect.value = currentYear;
  else if (years.length > 0) yearSelect.value = years[0];
}

// ===== 2. DOANH THU 12 THÁNG =====
let chartDoanhThu = null;
let chartTyLe = null;   
async function loadDoanhThu() {
  const now = new Date();
  const monthSelect = document.getElementById("selectMonth");
  const yearSelect  = document.getElementById("selectYear");

  if (!monthSelect.dataset.loaded) {
    monthSelect.value = now.getMonth() + 1;
    monthSelect.dataset.loaded = "1";
  }

  const selectedMonth = monthSelect.value;
  const selectedYear  = yearSelect.value;

  const res = await fetch(`${API_BASE}/doanhthu?year=${selectedYear}&month=${selectedMonth}`);
  const data = await res.json();

  const labels = [];
  const values = new Array(31).fill(0);
  for (let i = 1; i <= 31; i++) labels.push("" + i);

  data.day.forEach((d, i) => { values[d - 1] = data.revenue[i]; });

  if (chartDoanhThu) chartDoanhThu.destroy();

  chartDoanhThu = new Chart(document.getElementById("chartDoanhThu"), {
    type: "line",
    data: { labels, datasets: [{ label:"", data: values, borderColor:"#c59d8c", borderWidth:2, fill:false, tension:0.3, pointRadius:4, pointBackgroundColor:"#c59d8c", pointHoverRadius:6 }] },
    options: {
      plugins: { legend: { display:false } },
      scales: {
        x: { title: { display:true, text:"Ngày", font:{ size:14 } } },
        y: {
          beginAtZero:true,
          title:{ display:true, text:"VND", font:{ size:14 } },
          ticks:{ callback:(value)=> value.toLocaleString() + " đ" }
        }
      }
    }
  });
}



// ===== 3. TỶ LỆ ĐƠN HÀNG =====
async function loadTyLe() {
  const month = document.getElementById("selectMonth").value;
  const year  = document.getElementById("selectYear").value;

  const res = await fetch(`${API_BASE}/tyle?year=${year}&month=${month}`);
  const raw = await res.json();

  const labels = [], values = [], colors = [];
  if (raw.hoan_thanh > 0) { labels.push("Hoàn thành"); values.push(raw.hoan_thanh); colors.push("#8bc34a"); }
  if (raw.dang_giao > 0)  { labels.push("Đang giao");  values.push(raw.dang_giao);  colors.push("#ffc107"); }
  if (raw.huy > 0)        { labels.push("Hủy");        values.push(raw.huy);        colors.push("#e57373"); }

  const total = values.reduce((a,b)=>a+b,0);
  if (chartTyLe) chartTyLe.destroy();

  chartTyLe = new Chart(document.getElementById("chartTyLe"), {
    type:"pie",
    data:{ labels, datasets:[{ data:values, backgroundColor:colors }] },
    plugins:[ChartDataLabels],
    options:{
      plugins:{
        datalabels:{
          formatter:(v)=> total ? ((v/total)*100).toFixed(1)+"%" : "0%",
          color:"#000",
          font:{ weight:"bold", size:14 }
        }
      }
    }
  });
}


// ===== 4. ĐƠN HÀNG =====
async function loadDonHang() {
    const res = await fetch(`${API_BASE}/donhang`);
    const data = await res.json();

    const tbody = document.querySelector("#tableDonHang tbody");
    tbody.innerHTML = "";

    data.forEach(d => {
        let cls = "status-done";
        if (d.trang_thai === "Đang giao hàng") cls = "status-shipping";
        if (d.trang_thai === "Đã hủy") cls = "status-cancel";

        tbody.innerHTML += `
        <tr>
            <td>${d.ma_don}</td>
            <td>${d.ma_kh}</td>
            <td>${d.tong_tien.toLocaleString()} đ</td>
            <td class="${cls}">${d.trang_thai}</td>
        </tr>`;
    });
}



// ===== 5. NGƯỜI DÙNG MỚI =====
async function loadNguoiDung() {
    const res = await fetch(`${API_BASE}/nguoidung`);
    const data = await res.json();

    const tbody = document.querySelector("#tableNguoiDung tbody");
    tbody.innerHTML = "";

    data.forEach(u => {
        tbody.innerHTML += `
        <tr>
            <td>${u.username}</td>
            <td>${u.email}</td>
            <td>${u.ngay_tao}</td>
            <td><span class="status-dot status-green"></span></td>
        </tr>`;
    });
}

function onFilterChange() {
  loadDoanhThu();
  loadTyLe();
}


// ==== Gọi tất cả API ====
(async function initDashboard() {
  loadOverview();

  await loadYears();

  // set tháng hiện tại
  document.getElementById("selectMonth").value = new Date().getMonth() + 1;

  loadDoanhThu();
  loadTyLe();

  loadDonHang();
  loadNguoiDung();
})();

</script>

</body>
</html>

<?php include 'includes/admin_footer.php'; ?>