

<h3>Your Saved Payment Methods</h3>

<?php foreach($paymentProfiles as $card): ?>

    <div>
        <p><?php print $card->firstName() . " " . $card->lastName() . "'s " . $card->type(); ?></p>
        <p>Card #: <?php print $card->number(); ?></p>
        <p>Expires on: <?php print $card->expiresOn(); ?></p>
        <p><strong>Billing Address</strong></p>
        <p>
            <?php print $card->address() . ", " . $card->city() . ", " . $card->state() . " " . $card->zip(); ?>
        </p>

        <button>Delete</button>
        <button>&nbsp;&nbsp;Edit&nbsp;&nbsp;</button>
    </div>



    <br />
    <br />

<?php endforeach; ?>