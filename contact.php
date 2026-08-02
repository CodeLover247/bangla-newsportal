<?php
require_once __DIR__ . '/includes/header.php';

$message_sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $msg = sanitize($_POST['message'] ?? '');

    if (!empty($name) && !empty($email) && !empty($msg)) {
        $message_sent = true;
    }
}
?>

<div class="container my-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="bg-white p-4 rounded border shadow-sm">
                <h1 class="font-serif fw-bold mb-3"><i class="bi bi-envelope-at-fill text-danger me-2"></i> Contact Editorial Office</h1>
                <p class="text-muted mb-4">Have news tips, press releases, or general inquiries? Send a message directly to our editorial team.</p>

                <?php if ($message_sent): ?>
                    <div class="alert alert-success p-3 mb-4">
                        <i class="bi bi-check-circle-fill me-2"></i> Thank you! Your message has been received. Our editorial team will review it shortly.
                    </div>
                <?php endif; ?>

                <form action="contact.php" method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Full Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="John Doe">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Email Address *</label>
                        <input type="email" name="email" class="form-control" required placeholder="john@example.com">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Subject *</label>
                        <input type="text" name="subject" class="form-control" required placeholder="News Tip / Editorial Query">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Message *</label>
                        <textarea name="message" class="form-control" rows="5" required placeholder="Write your message here..."></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-danger btn-lg px-4 fw-bold">Send Message</button>
                    </div>
                </form>

                <!-- Google Map Embed -->
                <div class="mt-5 pt-3 border-top">
                    <h5 class="fw-bold mb-3">Office Location</h5>
                    <div class="ratio ratio-21x9 rounded overflow-hidden border">
                        <iframe src="<?= htmlspecialchars(get_setting('google_map')) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-3 border shadow-sm mb-4">
                <h5 class="fw-bold border-bottom pb-2 mb-3">Office Information</h5>
                <p class="small mb-2"><strong class="text-dark">Address:</strong><br><?= htmlspecialchars(get_setting('address')) ?></p>
                <p class="small mb-2"><strong class="text-dark">Phone:</strong><br><?= htmlspecialchars(get_setting('phone')) ?></p>
                <p class="small mb-2"><strong class="text-dark">Mobile:</strong><br><?= htmlspecialchars(get_setting('mobile')) ?></p>
                <p class="small mb-2"><strong class="text-dark">Email:</strong><br><?= htmlspecialchars(get_setting('email')) ?></p>
                <p class="small mb-0"><strong class="text-dark">Operating Hours:</strong><br><?= htmlspecialchars(get_setting('office_time')) ?></p>
            </div>
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
