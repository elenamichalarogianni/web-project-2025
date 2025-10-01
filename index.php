<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ακαδημαϊκό Πληροφοριακό Σύστημα</title>
    <link rel="stylesheet" type="text/css" href="assets/css/format.css">
    <style>
      .blue-bar {
        width: 100%;
        height: 80px;
        background-color: #1f4d64;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1000;
      }
      body { margin: 0; padding-top: 60px; }
    </style>
</head>
<body>
    <div class="blue-bar"></div>
    <h1 class="page-title">Ακαδημαϊκό Πληροφοριακό Σύστημα</h1>

    <div class="container">
      <div id="form">
        <h2>Login Form</h2>
        
        <form id="loginForm">
          </br>
          <label>Email: </label>
          <input type="text" id="user" name="email"></br>
          </br>
          <label>Password: </label>
          <input type="password" id="pass" name="password"></br><br>
          <input type="submit" id="btn" value="Login"/>
        </form>
        <div id="loginError" style="color:#b00; margin-top:10px; display:none;"></div>
      </div>

      <div class="announcements-box">
        <h3>Ανακοινώσεις Παρουσιάσεων Διπλωματικών</h3>
        <div class="date-filters">
          <label>Από:</label>
          <input type="date">
          <label>Έως:</label>
          <input type="date">
          <button>Αναζήτηση</button>
        </div>
        <div class="announcement">
          <strong>16 Απριλίου 2024</strong> – Παρουσίαση Διπλωματικής: Συστήματα Τεχνητής Νοημοσύνης.
        </div>
        <div class="announcement">
          <strong>10 Απριλίου 2024</strong> – Παρουσίαση Διπλωματικής: Ανάλυση Δεδομένων Μεγάλου Όγκου.
        </div>
        <div class="announcement">
          <strong>4 Απριλίου 2024</strong> – Παρουσίαση Διπλωματικής: Εφαρμογές στην Επεξεργασία Σήματος.
        </div>
      </div>
    </div>

    
    <script src="assets/js/login.js"></script>
</body>
</html>