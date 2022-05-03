<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">


<div class="container px-5">

    <h2> Payment Method</h2>

    <form method="post" action="/card/save" enctype="multipart/form-data">

        <input type="hidden" name="id" value="<?php print $profile->id(); ?>" >

        <div class="row">
            <div class="col-md-8">
                <div class="card p-3">

                    <h6 class="text-uppercase">Payment details</h6>

                    <div class="row mt-2">

                        <div class="col-md-6">
                            <div class="inputbox mt-3 mr-2">
                                <input type="text" name="firstName" class="form-control" value="<?php print $profile->firstName(); ?>" required="required" />
                                <span>First Name</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="inputbox mt-3 mr-2">
                                <input type="text" name="lastName" class="form-control" value="<?php print $profile->lastName(); ?>" required="required" />
                                <span>Last Name</span>
                            </div>
                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-6">
                            <div class="d-flex flex-row">

                            <div class="form-check mt-3 ml-2">
                                <input class="form-check-input" type="checkbox" name="default" value="1" >
                                <span>Set as default</span>
                            </div>

                            </div>
                        </div>

                    </div>



                    <div class="mt-4 mb-4">

                        <h6 class="text-uppercase">Billing Information</h6>


                        <div class="row mt-3">

                            <div class="col-md-6">
                                <div class="inputbox mt-3 mr-2">
                                    <input type="text" name="address" class="form-control" value="<?php print $profile->address(); ?>" required="required" />
                                    <span>Street Address</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="inputbox mt-3 mr-2">
                                    <input type="text" name="city" class="form-control" value="<?php print $profile->city(); ?>" required="required" />
                                    <span>City</span>
                                </div>  
                            </div>

                        </div>


                        <div class="row mt-2">

                            <div class="col-md-6">
                                <div class="inputbox mt-3 mr-2">
                                    <input type="text" name="state" class="form-control" value="<?php print $profile->state(); ?>" required="required" />
                                    <span>State/Province</span>
                                </div>
                            </div>


                            <div class="col-md-6">
                                <div class="inputbox mt-3 mr-2">
                                    <input type="text" name="zip" class="form-control" value="<?php print $profile->zip(); ?>" required="required" />
                                    <span>Zip code</span>
                                </div>
                            </div>

                        </div>

                        <div class="col-md-6">
                            <div class="inputbox mt-3 mr-2">
                                <input type="tel" name="phone" class="form-control" value="<?php print $profile->phone(); ?>" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" placeholder="555-555-5555" required />
                                <span>Phone</span>
                            </div>
                        </div>

                    </div>

                </div>


                <div class="mt-4 mb-4 d-flex justify-content-between">
                    <button class="btn btn-success px-3" type="submit">Save Card</button>
                </div>

            </div>
        </div>
    </form>

</div>