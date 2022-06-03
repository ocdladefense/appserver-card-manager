
<?php $default = $card->isDefault() ? "default" : ""; ?>

<div class="card m-1 <?php print $default; ?>">
    <div class="card-body">
        
        <p class="card-title">
            <?php print $card->firstName() . " " . $card->lastName() . "'s " . $card->type(); ?>
            <?php if($card->isDefault()) print "(default)"; ?> 
        </p>

        <p>Card ending in &bull;&bull;&bull;&bull; <?php print $card->lastFour(); ?></p>
        <p>Expires on: <?php print $card->expMonth() . "-" . $card->expYear() ?></p>

        <div class="section mb-4 mt-2">
            <p><strong>Billing Information</strong></p>
            <p><?php print $card->firstName() . " " . $card->lastName(); ?></p>
            <p><?php print $card->address(); ?></p>
            <p><?php print $card->city() . ", " . $card->state() . " " . $card->zip(); ?></p>
            <p><?php print $card->phone(); ?></p>
        </div>

        <form class="btn-form" action="/card/<?php print $card->id(); ?>/delete" onSubmit="return confirm('Are you sure you want to delete this payment method?');">
            <button class="btn btn-primary">Delete</button>
        </form>

        <form class="btn-form" action="/card/<?php print $card->id(); ?>/edit" >
            <button class="btn btn-primary">Edit Card</button>
        </form>

    </div>
</div>