
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

<style>
    .profile{
        padding: .7vw;
    }
</style>

<div class="container text-center vw-100">
    <h3>Your Saved Payment Methods</h3>
</div>

<div class="container vw-100">
    <div class="row">

    <?php foreach($paymentProfiles as $card): ?>

        <div class="col-md-4 bg-light px-6 border border-primary profile">

            <p><?php print $card->firstName() . " " . $card->lastName() . "'s " . $card->type(); ?></p>
            <p>Card #: <?php print $card->number(); ?></p>
            <p>Expires on: <?php print $card->expiresOn(); ?></p>
            <p><strong>Billing Address</strong></p>
            <p>
                <?php print $card->address() . ", " . $card->city() . ", " . $card->state() . " " . $card->zip(); ?>
            </p>

            <button class="btn btn-primary">Delete</button>
            <button class="btn btn-primary">&nbsp;&nbsp;Edit&nbsp;&nbsp;</button>
            
        </div>



    <?php endforeach; ?>

    </div>
</div>