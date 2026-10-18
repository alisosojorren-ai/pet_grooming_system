<?php
require 'includes/config.php';
require 'includes/functions.php';

// Get all services
$services = $conn->query("SELECT * FROM services ORDER BY price");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services & Pricing - PetCare Grooming</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins',sans-serif;color:#2d3748;background:#f7fafc}
     .navbar{background:white;padding:20px 40px;box-shadow:0 2px 20px rgba(0,0,0,0.08);position:sticky;top:0;z-index:1000}
     .nav-content{max-width:1400px;margin:0 auto;display:flex;justify-content:space-between;align-items:center}
     .logo{display:flex;align-items:center;gap:12px;font-size:26px;font-weight:800;color:#8b5cf6;text-decoration:none}
     .logo span{font-size:36px}
     .nav-links{display:flex;gap:30px;align-items:center}
     .nav-links a{text-decoration:none;color:#4a5568;font-weight:500;transition:0.3s}
     .nav-links a:hover{color:#8b5cf6}
     .btn-nav{padding:12px 28px;background:linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%);color:white!important;border-radius:10px;font-weight:600}
     .hero-small{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:120px 40px 60px;text-align:center;color:white}
     .hero-small h1{font-size:48px;font-weight:800;margin-bottom:15px}
     .hero-small p{font-size:18px;opacity:0.95}
     .container{max-width:1200px;margin:0 auto;padding:80px 40px}
     .section-header{text-align:center;margin-bottom:60px}
     .section-header h2{font-size:42px;font-weight:800;color:#2d3748;margin-bottom:15px}
     .section-header p{font-size:18px;color:#718096}
     .services-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:30px}
     .service-card{background:white;padding:40px;border-radius:20px;box-shadow:0 4px 20px rgba(0,0,0,0.08);transition:0.4s;position:relative;overflow:hidden}
     .service-card:hover{transform:translateY(-10px);box-shadow:0 15px 40px rgba(139,92,246,0.2)}
     .service-card.popular::before{content:'MOST POPULAR';position:absolute;top:20px;right:-30px;background:#8b5cf6;color:white;padding:5px 40px;transform:rotate(45deg);font-size:11px;font-weight:700;letter-spacing:1px}
     .service-icon{font-size:60px;margin-bottom:20px;display:inline-block}
     .service-price{font-size:48px;font-weight:800;color:#8b5cf6;margin-bottom:10px}
     .service-name{font-size:24px;font-weight:700;margin-bottom:12px;color:#2d3748}
     .service-desc{color:#718096;font-size:15px;line-height:1.7;margin-bottom:20px}
     .service-features{list-style:none;margin-bottom:25px}
     .service-features li{padding:10px 0;color:#4a5568;font-size:14px;display:flex;align-items:center;gap:10px}
     .service-features li::before{content:'✓';color:#68d391;font-weight:700;font-size:18px}
     .service-duration{display:inline-block;padding:8px 16px;background:#f3e8ff;color:#7c3aed;border-radius:20px;font-size:13px;font-weight:600;margin-bottom:25px}
     .btn-book{width:100%;padding:16px;background:linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%);color:white;border:none;border-radius:12px;font-size:16px;font-weight:700;cursor:pointer;transition:0.3s;text-decoration:none;display:block;text-align:center;box-shadow:0 4px 15px rgba(139,92,246,0.4)}
     .btn-book:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(139,92,246,0.6)}
     .info-section{padding:80px 40px;background:white}
     .info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:40px}
     .info-item{text-align:center}
     .info-icon{font-size:50px;margin-bottom:15px}
     .info-item h4{font-size:20px;margin-bottom:10px;color:#2d3748}
     .info-item p{color:#718096;font-size:15px}
     .footer{background:#2d3748;color:white;padding:40px;text-align:center}
        @media(max-width:768px){
           .hero-small h1{font-size:32px}
           .nav-links{display:none}
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-content">
            <a href="index.php" class="logo">
                <span>🐾</span>
                PetCare Grooming
            </a>
            <div class="nav-links">
                <a href="<?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'login.php' ?>">Home</a>
                <a href="services.php" style="color:#8b5cf6">Services</a>
                <?php if(isset($_SESSION['user_id'])):?>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="logout.php">Logout</a>
                <?php else:?>
                    <a href="login.php">Login</a>
                    <a href="register.php" class="btn-nav">Get Started</a>
                <?php endif;?>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero-small">
        <h1>Our Services & Pricing</h1>
        <p>Professional grooming packages for every pet</p>
    </section>

    <!-- Services Grid -->
    <div class="container">
        <div class="section-header">
            <h2>Choose Your Package</h2>
            <p>All services include free consultation and after-care tips</p>
        </div>

        <div class="services-grid">
            <?php
            $count = 0;
            while($srv = $services->fetch_assoc()):
                $count++;
                $isPopular = $count == 2; // Mark 2nd item as popular
           ?>
            <div class="service-card <?= $isPopular?'popular':''?>">
                <div class="service-icon">
                    <?php
                        $icons = ['🛁','✂️','💅','✨'];
                        echo $icons[($count-1)%4];
                   ?>
                </div>
                <div class="service-price">₱<?= number_format($srv['price'],0)?></div>
                <div class="service-name"><?= htmlspecialchars($srv['service_name'])?></div>
                <div class="service-desc"><?= htmlspecialchars($srv['description'])?></div>
                <div class="service-duration">⏱️ <?= $srv['duration_mins']?> minutes</div>

                <ul class="service-features">
                    <li>Professional grooming</li>
                    <li>Safe & gentle handling</li>
                    <li>Quality products used</li>
                    <li>Free pet treats</li>
                </ul>

                <?php if(isset($_SESSION['user_id'])):?>
                    <a href="book_appointment.php?service_id=<?= $srv['id']?>" class="btn-book">Book This Service</a>
                <?php else:?>
                    <a href="register.php" class="btn-book">Sign Up to Book</a>
                <?php endif;?>
            </div>
            <?php endwhile;?>
        </div>
    </div>

    <!-- Info Section -->
    <section class="info-section">
        <div class="container">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon">💯</div>
                    <h4>Satisfaction Guaranteed</h4>
                    <p>Not happy? We'll re-groom for free within 48 hours</p>
                </div>
                <div class="info-item">
                    <div class="info-icon">🏆</div>
                    <h4>Certified Groomers</h4>
                    <p>All staff are trained and certified professionals</p>
                </div>
                <div class="info-item">
                    <div class="info-icon">🧼</div>
                    <h4>Premium Products</h4>
                    <p>Only hypoallergenic, pet-safe shampoos used</p>
                </div>
                <div class="info-item">
                    <div class="info-icon">📸</div>
                    <h4>Before/After Photos</h4>
                    <p>See your pet's transformation every visit</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2026 PetCare Grooming. All rights reserved. Made with ❤️ for pets.</p>
    </footer>
</body>
</html