<?php
$this->load->view('layouts/header', array(
    'page_title' => 'داشبورد'
));
?>

<div class="container mt-5">

    <!-- Logout Button -->
    <div class="d-flex justify-content-start mb-4" dir="ltr">

        <button
            type="button"
            id="logoutButton"
            class="btn btn-outline-danger">

            خروج از حساب

        </button>

    </div>

    <!-- Dashboard Message -->
    <div
        id="dashboardMessage"
        class="alert d-none"
        role="alert">
    </div>

    <!-- Balance Cards -->
    <div class="row justify-content-center mb-4">

        <!-- Current Balance -->
        <div class="col-md-5 mb-3">

            <div class="card shadow border-0">

                <div class="card-body text-center">

                    <h5 class="text-secondary mb-3">
                        موجودی فعلی
                    </h5>

                    <h2
                        id="balanceAmount"
                        class="mb-0">

                        در حال دریافت...

                    </h2>

                </div>

            </div>

        </div>


        <!-- Debt -->
        <div class="col-md-5 mb-3">


            <div class="card shadow border-0">


                <div class="card-body text-center">


                    <h5 class="text-secondary mb-3">
                        بدهی
                    </h5>


                    <h2
                        id="debtAmount"
                        class="mb-0">

                        0 تومان

                    </h2>


                </div>


            </div>


        </div>



    </div>






    <!-- Update Balance Form -->
    <div class="row justify-content-center">


        <div class="col-md-6">


            <div class="card shadow border-0">


                <div class="card-body">


                    <h5 class="text-center mb-4">
                        تغییر موجودی
                    </h5>



                    <form id="balanceForm">


                        <div class="mb-3">


                            <label
                                for="balanceInput"
                                class="form-label">

                                مبلغ جدید

                            </label>



                            <div
                                class="input-group"
                                dir="ltr">


                                <span class="input-group-text">
                                    تومان
                                </span>


                                <input
                                    type="text"
                                    inputmode="numeric"
                                    class="form-control"
                                    id="balanceInput"
                                    dir="rtl"
                                    placeholder="مثلاً 500,000"
                                    autocomplete="off">


                            </div>


                        </div>





                        <button
                            type="submit"
                            class="btn btn-primary w-100">

                            ذخیره موجودی

                        </button>


                        <button
                            type="button"
                            id="resetBalanceButton"
                            class="btn btn-outline-danger w-100 mt-2">

                            صفر کردن موجودی
                        </button>


                    </form>



                </div>


            </div>


        </div>


    </div>



</div>





<script src="<?php echo base_url('assets/js/dashboard.js'); ?>"></script>



<?php
$this->load->view('layouts/footer');
?>
