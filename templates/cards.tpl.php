
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

            <?php module_template("card", __DIR__, $card); ?>

        <?php endforeach; ?>

    </div>
</div>