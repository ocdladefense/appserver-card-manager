<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">


<div class="container px-5">

    <h2>Add a New Payment Method</h2>

    <form method="post" action="/card/save" enctype="multipart/form-data">

        <div class="row">
            <div class="col-md-8">
                <div class="card p-3">

                    <h6 class="text-uppercase">Payment details</h6>

                    <div class="row mt-2">

                        <div class="col-md-6">
                            <div class="inputbox mt-3 mr-2">
                                <input type="text" name="firstName" class="form-control" required="required" />
                                <span>First Name</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="inputbox mt-3 mr-2">
                                <input type="text" name="lastName" class="form-control" required="required" />
                                <span>Last Name</span>
                            </div>
                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-6">
                            <div class="inputbox mt-3 mr-2 w-75">
                                <input type="text" name="cardNumber" class="form-control" maxlength="16" minlength="16" required />
                                <i class="fa fa-credit-card"></i>
                                <span>Card Number</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex flex-row">

                            <div class="inputbox mt-3 mr-2 w-25">
                                    <input type="text" name="expMonth" class="form-control" maxlength="2" minlength="2" placeholder="mm" required />
                                <span>exp. month</span>
                            </div>

                            &nbsp;
                            &nbsp;

                            <div class="inputbox mt-3 mr-2 w-25">
                                    <input type="text" name="expYear" class="form-control" maxlength="4" minlength="4" placeholder="yyyy" required />
                                <span>exp. year</span>
                            </div>

                            &nbsp;
                            &nbsp;

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
                                    <input type="text" name="address" class="form-control" required />
                                    <span>Street Address</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="inputbox mt-3 mr-2">
                                    <input type="text" name="city" class="form-control" required />
                                    <span>City</span>
                                </div>  
                            </div>

                        </div>


                        <div class="row mt-2">

                            <div class="col-md-6">
                                <div class="inputbox mt-3 mr-2">
                                    <input type="text" name="state" class="form-control" required />
                                    <span>State/Province</span>
                                </div>
                            </div>


                            <div class="col-md-6">
                                <div class="inputbox mt-3 mr-2">
                                    <input type="text" name="zip" class="form-control" required />
                                    <span>Zip code</span>
                                </div>
                            </div>

                        </div>

                        <div class="col-md-6">
                            <div class="inputbox mt-3 mr-2">
                                <input type="tel" name="phone" class="form-control" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" placeholder="555-555-5555" required />
                                <span>Phone</span>
                            </div>
                        </div>

                    </div>

                </div>


                <div class="row">
                    <div class="mt-4 mb-4">
                        <button class="btn btn-success px-3 mx-4" type="submit">Save Card</button>
                        <button class="btn btn-secondary px-3 mx-4" type="button" onClick="history.go(-1);">
                            &nbsp;&nbsp;&nbsp;Cancel&nbsp;&nbsp;&nbsp;
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>

</div>