
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
<link rel="stylesheet" type="text/css" href="<?php print module_path(); ?>/assets/css/card-list.css" />




<div class="container text-center vw-100 mb-4">
    <p class="h3">Your Saved Payment Methods &nbsp; <a class="text-decoration-none" href="/card/create">&#43;&nbsp;add card</a></p>
</div>

<?php if(count($paymentProfiles) == 0) : ?>
    <div class="container text-center vw-100 mb-4">
        <p class="h3">You don't have any saved payment methods.</p>
    </div>
<?php endif; ?>


<div class="container vw-100">
    <div class="row">

        <?php foreach($paymentProfiles as $card): ?>

            <?php $default = $card->isDefault() ? "default" : ""; ?>

            <div class="card m-1 <?php print $default; ?>">
                <div class="card-body">
                    
                    <p class="card-title">
                        <?php print $card->firstName() . " " . $card->lastName() . "'s " . $card->type(); ?>
                        <?php if($card->isDefault()) print "(default)"; ?> 
                    </p>

                    <p>Card ending in &bull;&bull;&bull;&bull; <?php print $card->lastFour(); ?></p>
                    <p>Expires on: <?php print $card->expiresOn(); ?></p>

                    <div class="section mb-4 mt-2">
                        <p><strong>Billing Information</strong></p>
                        <p><?php print $card->firstName() . " " . $card->lastName(); ?></p>
                        <p><?php print $card->address(); ?></p>
                        <p><?php print $card->city() . ", " . $card->state() . " " . $card->zip(); ?></p>
                        <p><?php print $card->phone(); ?></p>
                    </div>

                    <form class="btn-form" action="/card/delete/<?php print $card->id(); ?>" onSubmit="return confirm('Are you sure you want to delete this payment method?');">
                        <button class="btn btn-primary">Delete</button>
                    </form>

                    <form class="btn-form" action="/card/edit/<?php print $card->id(); ?>" >
                        <button class="btn btn-primary">Edit Card</button>
                    </form>

                </div>
            </div>



        <?php endforeach; ?>

    </div>
</div>