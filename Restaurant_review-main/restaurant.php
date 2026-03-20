<?php
$isAuthenticated = isset($_GET['auth']) && $_GET['auth'] === '1';
$currentRole = isset($_GET['role']) ? $_GET['role'] : 'guest';

$restaurant = [
    'name' => 'Nasi & Co.',
    'rating' => '4.7 / 5',
    'cuisine' => 'Malaysian',
    'price_range' => '$$',
    'address' => '12 Jalan Merdeka, Kuala Lumpur',
    'hours' => '10:00 AM - 10:00 PM',
    'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=80'
];

$reviews = [
    [
        'reviewer' => 'Sarah Lee',
        'rating' => '5 / 5',
        'date' => '2026-03-10',
        'comment' => 'Great ambience, fast service, and the nasi lemak was excellent.'
    ],
    [
        'reviewer' => 'Daniel Goh',
        'rating' => '4 / 5',
        'date' => '2026-03-09',
        'comment' => 'Very good food overall. The sambal stood out and the portions were generous.'
    ],
    [
        'reviewer' => 'Aisha Tan',
        'rating' => '5 / 5',
        'date' => '2026-03-08',
        'comment' => 'One of the better casual dining spots I have visited recently. Would come again.'
    ]
];

$reviewError = '';
$reviewSuccess = '';
$submittedReview = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!$isAuthenticated || $currentRole !== 'diner') {
        $reviewError = 'Only signed-in diners can leave reviews in this prototype.';
    } else {
        $reviewerName = trim($_POST['reviewer_name'] ?? '');
        $reviewRating = trim($_POST['review_rating'] ?? '');
        $reviewComment = trim($_POST['review_comment'] ?? '');

        if ($reviewerName === '' || $reviewRating === '' || $reviewComment === '') {
            $reviewError = 'Please complete your name, rating, and comment before submitting your review.';
        } else {
            $submittedReview = [
                'reviewer' => $reviewerName,
                'rating' => $reviewRating . ' / 5',
                'date' => date('Y-m-d'),
                'comment' => $reviewComment
            ];
            $reviewSuccess = 'Your review has been submitted successfully for this prototype preview.';
            array_unshift($reviews, $submittedReview);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Restaurant Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include("includes/header.php"); ?>

    <main class="py-5">
        <div class="container">
            <div class="card shadow-sm border-0 p-4 mb-4">
                <div class="row g-4 align-items-stretch">
                    <div class="col-lg-6">
                        <div class="h-100">
                            <img src="<?php echo htmlspecialchars($restaurant['image']); ?>" alt="<?php echo htmlspecialchars($restaurant['name']); ?>" class="img-fluid rounded w-100 h-100 object-fit-cover" style="min-height: 320px; max-height: 420px; object-fit: cover;">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="h-100 d-flex flex-column justify-content-center border rounded p-4 bg-light">
                            <h1 class="h2 mb-3"><?php echo htmlspecialchars($restaurant['name']); ?></h1>
                            <p class="mb-2"><strong>Rating:</strong> <?php echo htmlspecialchars($restaurant['rating']); ?></p>
                            <p class="mb-2"><strong>Cuisine:</strong> <?php echo htmlspecialchars($restaurant['cuisine']); ?></p>
                            <p class="mb-2"><strong>Price Range:</strong> <?php echo htmlspecialchars($restaurant['price_range']); ?></p>
                            <p class="mb-2"><strong>Address:</strong> <?php echo htmlspecialchars($restaurant['address']); ?></p>
                            <p class="mb-0"><strong>Opening Hours:</strong> <?php echo htmlspecialchars($restaurant['hours']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($isAuthenticated && $currentRole === 'diner'): ?>
                <div class="card shadow-sm border-0 p-4 mb-4">
                    <h2 class="h4 mb-3">Leave a Review</h2>
                    <p class="text-muted">As a diner, you can submit a review for this restaurant in the prototype.</p>

                    <?php if ($reviewError !== ''): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($reviewError); ?></div>
                    <?php endif; ?>

                    <?php if ($reviewSuccess !== ''): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($reviewSuccess); ?></div>
                    <?php endif; ?>

                    <form method="post" action="restaurant.php?auth=1&role=diner">
                        <input type="hidden" name="submit_review" value="1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="reviewer_name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="reviewer_name" name="reviewer_name" value="<?php echo isset($_POST['reviewer_name']) ? htmlspecialchars($_POST['reviewer_name']) : 'Sample Diner'; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="review_rating" class="form-label">Rating</label>
                                <select class="form-select" id="review_rating" name="review_rating">
                                    <option value="">Select a rating</option>
                                    <option value="5">5</option>
                                    <option value="4">4</option>
                                    <option value="3">3</option>
                                    <option value="2">2</option>
                                    <option value="1">1</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="review_comment" class="form-label">Comment</label>
                                <textarea class="form-control" id="review_comment" name="review_comment" rows="4" placeholder="Share your dining experience"></textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </div>
                    </form>
                </div>
            <?php elseif ($isAuthenticated && $currentRole !== 'diner'): ?>
                <div class="alert alert-info mb-4">Only diner accounts can submit reviews. Restaurant owners and admins can still view all reviews in this prototype.</div>
            <?php else: ?>
                <div class="alert alert-secondary mb-4">Log in as a diner to leave a review for this restaurant.</div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 p-4">
                <h2 class="h4 mb-4">Reviews Section</h2>
                <div class="row g-3">
                    <?php foreach ($reviews as $index => $review): ?>
                        <div class="col-12">
                            <div class="border rounded p-3 bg-white">
                                <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                                    <strong>Review <?php echo $index + 1; ?> - <?php echo htmlspecialchars($review['reviewer']); ?></strong>
                                    <span><?php echo htmlspecialchars($review['date']); ?></span>
                                </div>
                                <p class="mb-2"><strong>Rating:</strong> <?php echo htmlspecialchars($review['rating']); ?></p>
                                <p class="mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include("includes/footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
