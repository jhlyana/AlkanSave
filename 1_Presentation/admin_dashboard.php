<?php
session_start();

// Redirect if not admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: /AlkanSave/1_Presentation/login.html");
    exit();
}

require_once __DIR__ . '/../../3_Data/repositories/UserRepository.php';
$userRepo = new UserRepository();

// Get stats for dashboard
$totalUsers = $userRepo->getTotalUsers();
$activeUsers = $userRepo->getActiveUsers();
$inactiveUsers = $totalUsers - $activeUsers;
$activePercentage = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100) : 0;
$inactivePercentage = $totalUsers > 0 ? round(($inactiveUsers / $totalUsers) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard | AlkanSave</title>
  <link rel="icon" href="images/logo.svg" type="image/x-icon">
  <link rel="stylesheet" href="css/sharedLayout.css" />
  <link rel="stylesheet" href="css/admin_dashboard.css" />
  <link rel="stylesheet" href="css/logout.css" />
</head>
<body>
  <div class="sidebar">
    <div class="logo">
      <img src="images/logo.svg" alt="AlkanSave Logo"/>
      <h2><span class="alkan">Alkan</span><span class="save">Save</span></h2>
    </div>
    <nav>
      <a href="admin_dashboard.php" class="active"><img src="images/analytics_icon.svg" alt="Home">Analytics Dashboard</a>
      <a href="admin_userM.php"><img src="images/profile_icon.svg" alt="Savings & Goals">User Management</a>
      <a href="admin_category.php"><img src="images/category_icon.svg" alt="Transaction">Category Management</a>
      <a href="admin_actlog.php"><img src="images/activity_icon.svg" alt="Category">Activity Logs</a>
    </nav>
    <a href="logout.php" class="logout" id="logoutButton">
      <img src="images/logout_icon.svg" alt="Logout">Log Out
    </a>
  </div> 

  <div class="main-content">
    <div class="datetime-container">
      <div class="date-box" id="dateBox" data-label="DATE"></div>
      <div class="time-box" id="timeBox" data-label="TIME"></div>
    </div>

    <section class="home-content">
      <h1 class="welcome">Welcome, <span class="highlight-name">Admin!</span></h1>

      <div class="top-row">
        <div class="progress-ring">
          <svg class="progress-ring__svg" width="120" height="120">
            <circle class="progress-ring__circle-bg" r="50" cx="60" cy="60"/>
            <circle class="progress-ring__circle" id="savingsCircle" r="50" cx="60" cy="60" style="stroke-dashoffset: <?= 314 - (314 * $activePercentage / 100) ?>"/>
          </svg>
          <div class="progress-text" id="savingsText"><?= $activePercentage ?>%</div>
          <p class="label">Active Users</p>
        </div>
        <div class="progress-ring">
          <svg class="progress-ring__svg" width="120" height="120">
            <circle class="progress-ring__circle-bg" r="50" cx="60" cy="60"/>
            <circle class="progress-ring__circle" id="goalsCircle" r="50" cx="60" cy="60" style="stroke-dashoffset: <?= 314 - (314 * $inactivePercentage / 100) ?>"/>
          </svg>
          <div class="progress-text" id="goalsText"><?= $inactivePercentage ?>%</div>
          <p class="label">Inactive Users</p>
        </div>
        <div class="add-savings-box">
            <button class="add-goal-btn" id="viewDAccountsBtn" title="Add Goal" onclick="redirectToPage()">
                View Deactivated Accounts
            </button>
            <br>
            <div class="total-saved-box">
                <h3>Average Savings per Category</h3>
                <p>P <?= number_format($userRepo->getAverageSavings(), 2) ?></p>
            </div>
        </div>
        <div class="total-saved-box">
            <h3>Active Users this month</h3>
            <p><?= $userRepo->getActiveUsersThisMonth() ?></p>
        </div>
      </div>

      <div class="bottom-row">
        <div class="calendar">
          <h3 id="calendarMonthYear"><?= date('F Y') ?></h3>
          <table id="calendarTable"></table>
        </div>

        <div class="goals-deadline">
          <h3>Most Commonly Used Categories</h3>
          <?php
          $commonCategories = $userRepo->getCommonCategories();
          foreach ($commonCategories as $category): ?>
              <div class="goal-entry"><span><?= htmlspecialchars($category['name']) ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  </div>

  <div id="logoutModal" class="logout-modal">
    <div class="modal-content">
      <h3>Are you sure you want to log out?</h3>
      <div class="modal-buttons">
        <button id="confirmLogout" class="confirm-btn">Yes</button>
        <button id="cancelLogout" class="cancel-btn">No</button>
      </div>
    </div>
  </div>

  <script src="js/dateLinksLogout.js"></script>
  <script src="js/viewDeactivatedAcc.js"></script>
  <script src="js/user_home.js"></script>
</body>
</html>