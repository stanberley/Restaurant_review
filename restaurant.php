<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/includes/restaurant-db.php';

$isAuthenticated = isset($_SESSION['role']);
$currentRole     = $_SESSION['role'] ?? 'guest';
$currentUserId   = (int) ($_SESSION['user_id'] ?? 0);

$restaurantId = isset($_GET['id']) && ctype_digit($_GET['id']) && (int) $_GET['id'] > 0
    ? (int) $_GET['id'] : null;

$reviewError   = '';
$reviewSuccess = '';
$dataError     = '';

$connection = getDatabaseConnection($dataError);
$restaurant = null;
$reviews    = [];

if ($connection) {

    if ($restaurantId === null) {
        $restaurantId = getFirstRestaurantId($connection, $dataError);
    }
    if ($restaurantId === null && $dataError === '') {
        $dataError = 'No restaurants are available yet.';
    }

    // ── CREATE ────────────────────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
        if (!$isAuthenticated || $currentRole !== 'diner' || $currentUserId < 1) {
            $reviewError = 'Only signed-in diners can leave reviews.';
        } else {
            $reviewRating  = trim($_POST['review_rating']  ?? '');
            $reviewComment = trim($_POST['review_comment'] ?? '');
            if (!ctype_digit($reviewRating) || (int)$reviewRating < 1 || (int)$reviewRating > 5 || $reviewComment === '') {
                $reviewError = 'Please provide a valid rating (1–5) and a comment.';
            } else {
                $payload = [
                    'UserId'       => $currentUserId,
                    'RestaurantID' => $restaurantId,
                    'Rating'       => (int) $reviewRating,
                    'Comments'     => $reviewComment,
                ];
                if (insertReviewRecord($connection, $payload, $reviewError)) {
                    header('Location: restaurant.php?id=' . urlencode((string)$restaurantId) . '&review=saved');
                    $connection->close();
                    exit;
                }
            }
        }
    }

    // ── UPDATE ────────────────────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_review'])) {
        $editId      = (int) ($_POST['review_id']    ?? 0);
        $editRating  = trim($_POST['review_rating']  ?? '');
        $editComment = trim($_POST['review_comment'] ?? '');
        if (!$isAuthenticated || $currentRole !== 'diner' || $currentUserId < 1) {
            $reviewError = 'Only signed-in diners can edit reviews.';
        } elseif (!ctype_digit($editRating) || (int)$editRating < 1 || (int)$editRating > 5 || $editComment === '') {
            $reviewError = 'Please provide a valid rating (1–5) and a comment.';
        } else {
            if (updateReviewRecord($connection, $editId, $currentUserId, $editRating, $editComment, $reviewError)) {
                header('Location: restaurant.php?id=' . urlencode((string)$restaurantId) . '&review=updated');
                $connection->close();
                exit;
            }
        }
    }

    // ── DELETE ────────────────────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
        $deleteId = (int) ($_POST['review_id'] ?? 0);
        if (!$isAuthenticated || $currentRole !== 'diner' || $currentUserId < 1) {
            $reviewError = 'Only signed-in diners can delete reviews.';
        } else {
            if (deleteReviewRecord($connection, $deleteId, $currentUserId, $reviewError)) {
                header('Location: restaurant.php?id=' . urlencode((string)$restaurantId) . '&review=deleted');
                $connection->close();
                exit;
            }
        }
    }

    if ($restaurantId !== null) {
        $restaurant = getRestaurantById($connection, $restaurantId, $dataError);
    }
    if ($restaurant) {
        $reviews = getRestaurantReviews($connection, $restaurantId, $dataError);
    }

    $connection->close();
}

if (!$restaurant && $dataError === '') {
    $dataError = 'Restaurant was not found.';
}

if (isset($_GET['review'])) {
    $reviewSuccess = match($_GET['review']) {
        'saved'   => 'Your review has been submitted successfully.',
        'updated' => 'Your review has been updated.',
        'deleted' => 'Your review has been deleted.',
        default   => ''
    };
}

$averageRating = null;
if (count($reviews) > 0) {
    $sum = 0;
    foreach ($reviews as $r) { $sum += (int) $r['Rating']; }
    $averageRating = number_format($sum / count($reviews), 1);
}

$heroImage = $restaurant['ImageUrl'] ?? '';
if ($heroImage === '') {
    $heroImage = 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=80';
}

$editingReviewId = isset($_GET['edit']) && ctype_digit($_GET['edit']) ? (int)$_GET['edit'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $restaurant ? htmlspecialchars($restaurant['RestaurantName']) : 'Restaurant'; ?> — Foodview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --cream: #FAF8F4;
            --warm-dark: #1C1917;
            --warm-mid: #44403C;
            --warm-muted: #78716C;
            --accent: #C2410C;
            --accent-light: #FFF7ED;
            --accent-border: #FDBA74;
            --gold: #D97706;
            --border: #E7E5E4;
            --card-bg: #FFFFFF;
            --radius: 16px;
            --radius-sm: 10px;
        }
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: var(--cream); color: var(--warm-dark); margin: 0; }

        .navbar { box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); position: relative; z-index: 1000; }
        .navbar-custom { background-color: #1a1a1a; }

        .hero-wrap { position: relative; width: 100%; height: 420px; overflow: hidden; }
        .hero-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .hero-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(28,25,23,.82) 0%, rgba(28,25,23,.1) 60%); }
        .hero-content { position: absolute; bottom: 0; left: 0; right: 0; padding: 2rem 2.5rem; }
        .hero-badge { display: inline-block; background: var(--accent); color: #fff; font-size: 11px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; padding: 3px 12px; border-radius: 99px; margin-bottom: 10px; }
        .hero-title { font-family: 'Playfair Display', serif; font-size: clamp(1.8rem,4vw,2.8rem); font-weight: 700; color: #fff; margin: 0 0 12px; line-height: 1.2; }
        .hero-meta { display: flex; flex-wrap: wrap; gap: 18px; color: rgba(255,255,255,.85); font-size: 13.5px; }
        .hero-meta span { display: flex; align-items: center; gap: 6px; }
        .hero-meta i { color: var(--accent-border); }

        .info-strip { background: var(--card-bg); border-bottom: 1px solid var(--border); }
        .info-strip-inner { display: flex; flex-wrap: wrap; max-width: 960px; margin: 0 auto; }
        .ip { flex: 1; min-width: 120px; padding: 1rem 1.25rem; border-right: 1px solid var(--border); text-align: center; }
        .ip:last-child { border-right: none; }
        .ip-label { font-size: 10px; font-weight: 500; text-transform: uppercase; letter-spacing: .07em; color: var(--warm-muted); margin-bottom: 3px; }
        .ip-val { font-size: 14px; font-weight: 500; color: var(--warm-dark); }
        .ip-val.acc { color: var(--accent); }
        .ip-val.gold { color: var(--gold); }

        .page-body { max-width: 960px; margin: 0 auto; padding: 2.5rem 1.25rem 4rem; }
        .sec-head { font-family: 'Playfair Display', serif; font-size: 1.35rem; font-weight: 500; color: var(--warm-dark); margin-bottom: 1.1rem; padding-bottom: .5rem; border-bottom: 1px solid var(--border); }

        .fv-alert { border-radius: var(--radius-sm); padding: .8rem 1rem; font-size: 13.5px; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 10px; }
        .fv-alert.success { background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; }
        .fv-alert.danger  { background: #FFF1F2; color: #9F1239; border: 1px solid #FECDD3; }
        .fv-alert.info    { background: #EFF6FF; color: #1E40AF; border: 1px solid #BFDBFE; }
        .fv-alert.muted   { background: #F5F5F4; color: var(--warm-muted); border: 1px solid var(--border); }
        .fv-alert.warning { background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A; }

        .form-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.75rem 2rem; margin-bottom: 2rem; }
        .fv-label { font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: .06em; color: var(--warm-muted); margin-bottom: 5px; display: block; }
        .fv-input { width: 100%; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 9px 13px; font-size: 14px; font-family: 'DM Sans', sans-serif; color: var(--warm-dark); background: var(--cream); outline: none; transition: border-color .15s; }
        .fv-input:focus { border-color: var(--accent); background: #fff; }
        .fv-input[readonly] { opacity: .6; cursor: default; }
        textarea.fv-input { resize: vertical; min-height: 90px; line-height: 1.6; }

        .star-picker { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 2px; }
        .star-picker input[type=radio] { display: none; }
        .star-picker label { font-size: 26px; color: var(--border); cursor: pointer; transition: color .1s; line-height: 1; }
        .star-picker input[type=radio]:checked ~ label,
        .star-picker label:hover,
        .star-picker label:hover ~ label { color: var(--gold); }

        .btn-fv { display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-sm); padding: 8px 18px; font-size: 13px; font-weight: 500; font-family: 'DM Sans', sans-serif; cursor: pointer; border: none; transition: background .15s, transform .1s; text-decoration: none; }
        .btn-fv:hover { transform: translateY(-1px); }
        .btn-fv.primary   { background: var(--accent); color: #fff; }
        .btn-fv.primary:hover { background: #9A3412; color: #fff; }
        .btn-fv.secondary { background: transparent; color: var(--warm-mid); border: 1px solid var(--border); }
        .btn-fv.secondary:hover { border-color: var(--warm-mid); background: var(--cream); }
        .btn-fv.danger    { background: #FFF1F2; color: #9F1239; border: 1px solid #FECDD3; }
        .btn-fv.danger:hover { background: #FFE4E6; }
        .btn-fv.warning   { background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A; }
        .btn-fv.warning:hover { background: #FEF3C7; }
        .btn-fv.sm { padding: 5px 12px; font-size: 12px; }

        .review-list { display: flex; flex-direction: column; gap: 12px; }
        .rc { background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem 1.5rem; transition: box-shadow .15s; }
        .rc:hover { box-shadow: 0 4px 20px rgba(0,0,0,.06); }
        .rc.editing { border-color: var(--accent-border); background: #FFFBF5; }
        .rc-top { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
        .rc-left { display: flex; align-items: center; gap: 12px; }
        .av { width: 40px; height: 40px; border-radius: 50%; background: var(--accent-light); border: 1.5px solid var(--accent-border); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; color: var(--accent); flex-shrink: 0; }
        .rc-name { font-size: 14px; font-weight: 500; color: var(--warm-dark); }
        .rc-date { font-size: 11px; color: var(--warm-muted); margin-top: 1px; }
        .rc-stars { color: var(--gold); font-size: 15px; letter-spacing: 1px; }
        .rc-comment { font-size: 14px; color: var(--warm-mid); line-height: 1.65; margin-top: 6px; }
        .rc-actions { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--border); }
        .edit-form { margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--accent-border); }
        .edit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
        .no-reviews { text-align: center; padding: 3rem 1rem; color: var(--warm-muted); background: var(--card-bg); border: 1px dashed var(--border); border-radius: var(--radius); }
        .no-reviews i { font-size: 2rem; margin-bottom: .75rem; display: block; }
        .my-badge { display: inline-block; background: var(--accent-light); color: var(--accent); font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 99px; border: 1px solid var(--accent-border); margin-left: 6px; vertical-align: middle; }

        @media(max-width:600px) {
            .hero-wrap { height: 280px; }
            .hero-content { padding: 1.25rem; }
            .form-card { padding: 1.25rem; }
            .edit-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include("includes/header.php"); ?>

    <?php if ($dataError !== ''): ?>
        <div class="page-body">
            <div class="fv-alert danger"><i class="bi bi-exclamation-circle"></i><?php echo htmlspecialchars($dataError); ?></div>
        </div>
    <?php endif; ?>

    <?php if ($restaurant): ?>

    <div class="hero-wrap">
        <img src="<?php echo htmlspecialchars($heroImage); ?>" alt="<?php echo htmlspecialchars($restaurant['RestaurantName']); ?>">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <span class="hero-badge"><?php echo htmlspecialchars($restaurant['CusineType']); ?></span>
            <h1 class="hero-title"><?php echo htmlspecialchars($restaurant['RestaurantName']); ?></h1>
            <div class="hero-meta">
                <?php if ($averageRating !== null): ?>
                <span><i class="bi bi-star-fill"></i><?php echo $averageRating; ?> / 5 &nbsp;<span style="opacity:.6">(<?php echo count($reviews); ?> review<?php echo count($reviews) !== 1 ? 's' : ''; ?>)</span></span>
                <?php endif; ?>
                <span><i class="bi bi-geo-alt"></i><?php echo htmlspecialchars($restaurant['Address']); ?></span>
                <span><i class="bi bi-telephone"></i><?php echo htmlspecialchars($restaurant['PhoneNum']); ?></span>
            </div>
        </div>
    </div>

    <div class="info-strip">
        <div class="info-strip-inner">
            <div class="ip"><div class="ip-label">Price Range</div><div class="ip-val acc"><?php echo htmlspecialchars($restaurant['PriceRange']); ?></div></div>
            <div class="ip"><div class="ip-label">Opening Days</div><div class="ip-val"><?php echo htmlspecialchars($restaurant['OpeningDays'] ?? '—'); ?></div></div>
            <div class="ip"><div class="ip-label">Opens</div><div class="ip-val"><?php echo htmlspecialchars($restaurant['OpeningHours'] ?? '—'); ?></div></div>
            <div class="ip"><div class="ip-label">Closes</div><div class="ip-val"><?php echo htmlspecialchars($restaurant['ClosingHours'] ?? '—'); ?></div></div>
            <div class="ip"><div class="ip-label">Avg Rating</div><div class="ip-val gold"><?php echo $averageRating !== null ? '★ ' . $averageRating : '—'; ?></div></div>
        </div>
    </div>

    <div class="page-body">

        <?php if ($reviewSuccess !== ''): ?>
            <div class="fv-alert success"><i class="bi bi-check-circle"></i><?php echo htmlspecialchars($reviewSuccess); ?></div>
        <?php endif; ?>
        <?php if ($reviewError !== ''): ?>
            <div class="fv-alert danger"><i class="bi bi-exclamation-circle"></i><?php echo htmlspecialchars($reviewError); ?></div>
        <?php endif; ?>

        <!-- CREATE -->
        <?php if ($isAuthenticated && $currentRole === 'diner'): ?>
        <div class="form-card">
            <h2 class="sec-head">Leave a review</h2>
            <form method="post" action="restaurant.php?id=<?php echo urlencode((string)$restaurantId); ?>">
                <input type="hidden" name="submit_review" value="1">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                    <div>
                        <label class="fv-label">Your name</label>
                        <input type="text" class="fv-input" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" readonly>
                    </div>
                    <div>
                        <label class="fv-label">Your rating</label>
                        <div class="star-picker">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="review_rating" id="ns<?php echo $i; ?>" value="<?php echo $i; ?>"
                                <?php echo (($_POST['review_rating'] ?? '') == $i && !isset($_POST['edit_review'])) ? 'checked' : ''; ?>>
                            <label for="ns<?php echo $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <div style="margin-bottom:14px">
                    <label class="fv-label">Your review</label>
                    <textarea name="review_comment" class="fv-input" placeholder="Share your dining experience…"><?php echo htmlspecialchars(!isset($_POST['edit_review']) ? ($_POST['review_comment'] ?? '') : ''); ?></textarea>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <button type="submit" class="btn-fv primary"><i class="bi bi-send"></i> Submit review</button>
                    <a href="tip-prompt.php?restaurant_id=<?php echo $restaurantId; ?>" class="btn-fv warning"><i class="bi bi-cash-coin"></i> Leave a tip</a>
                </div>
            </form>
        </div>

        <?php elseif ($isAuthenticated && $currentRole !== 'diner'): ?>
            <div class="fv-alert info"><i class="bi bi-info-circle"></i>Only diner accounts can submit reviews. You can still read all reviews below.</div>
        <?php else: ?>
            <div class="fv-alert muted"><i class="bi bi-person-circle"></i>Log in as a diner to leave a review for this restaurant.</div>
        <?php endif; ?>

        <!-- READ -->
        <h2 class="sec-head">
            Reviews
            <?php if (count($reviews) > 0): ?>
                <span style="font-family:'DM Sans',sans-serif;font-size:12px;font-weight:400;color:var(--warm-muted);margin-left:8px"><?php echo count($reviews); ?> total</span>
            <?php endif; ?>
        </h2>

        <?php if (count($reviews) === 0): ?>
            <div class="no-reviews">
                <i class="bi bi-chat-square-text"></i>
                No reviews yet. Be the first to share your experience!
            </div>
        <?php else: ?>
        <div class="review-list">
            <?php foreach ($reviews as $review):
                $isOwner   = $isAuthenticated && $currentRole === 'diner' && (int)$review['UserId'] === $currentUserId;
                $isEditing = $isOwner && $editingReviewId === (int)$review['idReview'];
                $initials  = strtoupper(substr($review['reviewer_name'] ?? 'A', 0, 1));
                $rating    = (int) $review['Rating'];
                $stars     = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                $date      = date('d M Y, g:ia', strtotime($review['ReviewDate']));
            ?>
            <div class="rc <?php echo $isEditing ? 'editing' : ''; ?>" id="review-<?php echo $review['idReview']; ?>">
                <div class="rc-top">
                    <div class="rc-left">
                        <div class="av"><?php echo htmlspecialchars($initials); ?></div>
                        <div>
                            <div class="rc-name">
                                <?php echo htmlspecialchars($review['reviewer_name'] ?? 'Anonymous'); ?>
                                <?php if ($isOwner): ?><span class="my-badge">You</span><?php endif; ?>
                            </div>
                            <div class="rc-date"><?php echo $date; ?></div>
                        </div>
                    </div>
                    <div class="rc-stars"><?php echo $stars; ?></div>
                </div>

                <?php if (!$isEditing): ?>
                    <!-- READ view -->
                    <div class="rc-comment"><?php echo htmlspecialchars($review['Comments']); ?></div>

                    <?php if ($isOwner): ?>
                    <div class="rc-actions">
                        <a href="restaurant.php?id=<?php echo $restaurantId; ?>&edit=<?php echo $review['idReview']; ?>#review-<?php echo $review['idReview']; ?>"
                           class="btn-fv secondary sm"><i class="bi bi-pencil"></i> Edit</a>

                        <form method="post" action="restaurant.php?id=<?php echo $restaurantId; ?>"
                              onsubmit="return confirm('Delete this review? This cannot be undone.');" style="margin:0">
                            <input type="hidden" name="delete_review" value="1">
                            <input type="hidden" name="review_id" value="<?php echo $review['idReview']; ?>">
                            <button type="submit" class="btn-fv danger sm"><i class="bi bi-trash"></i> Delete</button>
                        </form>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- UPDATE inline edit form -->
                    <div class="edit-form">
                        <form method="post" action="restaurant.php?id=<?php echo $restaurantId; ?>">
                            <input type="hidden" name="edit_review" value="1">
                            <input type="hidden" name="review_id" value="<?php echo $review['idReview']; ?>">
                            <div class="edit-grid">
                                <div>
                                    <label class="fv-label">Update rating</label>
                                    <div class="star-picker">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" name="review_rating"
                                               id="es<?php echo $review['idReview'] . '_' . $i; ?>"
                                               value="<?php echo $i; ?>"
                                               <?php echo $rating === $i ? 'checked' : ''; ?>>
                                        <label for="es<?php echo $review['idReview'] . '_' . $i; ?>">★</label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div style="display:flex;align-items:flex-end;gap:6px">
                                    <button type="submit" class="btn-fv primary sm"><i class="bi bi-check-lg"></i> Save changes</button>
                                    <a href="restaurant.php?id=<?php echo $restaurantId; ?>" class="btn-fv secondary sm"><i class="bi bi-x"></i> Cancel</a>
                                </div>
                            </div>
                            <div>
                                <label class="fv-label">Update comment</label>
                                <textarea name="review_comment" class="fv-input"><?php echo htmlspecialchars($review['Comments']); ?></textarea>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>

    <?php include("includes/footer.php"); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var profileDropdown = document.getElementById('profileDropdown');
            if (!profileDropdown) {
                return;
            }

            var dropdownMenu = profileDropdown.nextElementSibling;
            if (!dropdownMenu || !dropdownMenu.classList.contains('dropdown-menu')) {
                return;
            }
            var dropdownParent = profileDropdown.closest('.dropdown');

            function closeMenu() {
                dropdownMenu.classList.remove('show');
                if (dropdownParent) {
                    dropdownParent.classList.remove('show');
                }
                profileDropdown.setAttribute('aria-expanded', 'false');
            }

            function openMenu() {
                dropdownMenu.classList.add('show');
                if (dropdownParent) {
                    dropdownParent.classList.add('show');
                }
                profileDropdown.setAttribute('aria-expanded', 'true');
            }

            profileDropdown.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var isOpen = dropdownMenu.classList.contains('show');
                if (isOpen) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });

            document.addEventListener('click', function (event) {
                if (event.target === profileDropdown || profileDropdown.contains(event.target) || dropdownMenu.contains(event.target)) {
                    return;
                }
                closeMenu();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeMenu();
                }
            });
        });
    </script>
</body>
</html>