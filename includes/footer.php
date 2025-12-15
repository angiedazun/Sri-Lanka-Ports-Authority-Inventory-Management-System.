    </div> <!-- End container -->
    
    <!-- Professional Beautiful Footer -->
    <footer class="professional-footer">
        <div class="footer-wave">
            <svg viewBox="0 0 1440 120" xmlns="http://www.w3.org/2000/svg">
                <path d="M0,64L48,69.3C96,75,192,85,288,80C384,75,480,53,576,48C672,43,768,53,864,58.7C960,64,1056,64,1152,58.7C1248,53,1344,43,1392,37.3L1440,32L1440,120L1392,120C1344,120,1248,120,1152,120C1056,120,960,120,864,120C768,120,672,120,576,120C480,120,384,120,288,120C192,120,96,120,48,120L0,120Z" fill="url(#gradient)"/>
                <defs>
                    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
                    </linearGradient>
                </defs>
            </svg>
        </div>
        
        <div class="footer-content">
            <div class="footer-section footer-about">
                <div class="footer-logo-section">
                    <div class="footer-logo-icon">
                        <i class="fas fa-anchor"></i>
                    </div>
                    <div class="footer-logo-text">
                        <h3>SLPA System</h3>
                        <p>Supply Logistics & Procurement</p>
                    </div>
                </div>
                <p class="footer-description">
                    Professional inventory management solution for Sri Lanka Ports Authority. 
                    Streamlining operations with cutting-edge technology.
                </p>
                <div class="footer-social">
                    <a href="#" class="social-icon" aria-label="Facebook" title="Facebook">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-icon" aria-label="Twitter" title="Twitter">
                        <i class="fa-brands fa-twitter"></i>
                    </a>
                    <a href="#" class="social-icon" aria-label="LinkedIn" title="LinkedIn">
                        <i class="fa-brands fa-linkedin-in"></i>
                    </a>
                    <a href="#" class="social-icon" aria-label="Instagram" title="Instagram">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4 class="footer-title">Quick Access</h4>
                <ul class="footer-menu">
                    <li><a href="../pages/dashboard.php"><i class="fas fa-chevron-right"></i> Dashboard</a></li>
                    <li><a href="../pages/toner_master.php"><i class="fas fa-chevron-right"></i> Toner Management</a></li>
                    <li><a href="../pages/papers_master.php"><i class="fas fa-chevron-right"></i> Papers Management</a></li>
                    <li><a href="../pages/ribbons_master.php"><i class="fas fa-chevron-right"></i> Ribbons Management</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4 class="footer-title">Operations</h4>
                <ul class="footer-menu">
                    <li><a href="../pages/toner_receiving.php"><i class="fas fa-chevron-right"></i> Receiving</a></li>
                    <li><a href="../pages/toner_issuing.php"><i class="fas fa-chevron-right"></i> Issuing</a></li>
                    <li><a href="../pages/toner_return.php"><i class="fas fa-chevron-right"></i> Returns</a></li>
                    <li><a href="../pages/users.php"><i class="fas fa-chevron-right"></i> User Management</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4 class="footer-title">Contact Us</h4>
                <ul class="footer-contact">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Sri Lanka Ports Authority<br>19, Chaithya Road, Colombo 01</span>
                    </li>
                    <li>
                        <i class="fas fa-phone-alt"></i>
                        <span>+94 11 242 1201</span>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <span>info@slpa.lk</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <p class="copyright">
                    <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> Sri Lanka Ports Authority. All Rights Reserved.
                </p>
                <div class="footer-badges">
                    <span class="badge-pill">
                        <i class="fas fa-shield-alt"></i> Secure System
                    </span>
                    <span class="badge-pill">
                        <i class="fas fa-lock"></i> SSL Protected
                    </span>
                    <span class="badge-pill">
                        <i class="fas fa-check-circle"></i> Verified
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop" onclick="scrollToTop()">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- JavaScript -->
    <script src="../assets/js/main.js"></script>
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <script>
        // Back to Top Button
        const backToTopButton = document.getElementById('backToTop');
        
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        });
        
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // Footer animation on scroll
        const footerObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('footer-visible');
                }
            });
        }, { threshold: 0.1 });
        
        const footerSections = document.querySelectorAll('.footer-section');
        footerSections.forEach(section => footerObserver.observe(section));
    </script>
</body>
</html>