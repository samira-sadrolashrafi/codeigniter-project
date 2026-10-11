<?php
$this->load->view('layouts/header', array(
    'page_title' => 'داشبورد'
));
?>


<div class="container mt-5">

    <div class="d-flex justify-content-start mb-4" dir="ltr">
        <button type="button" id="logoutButton" class="btn btn-outline-danger">
            خروج از حساب
        </button>
    </div>


    <div class="row justify-content-center">


        <div class="col-md-6">


            <div class="card shadow">


                <div class="card-body text-center">


                    <h4 class="mb-4">
                        موجودی حساب
                    </h4>


                    <div
                        id="dashboardMessage"
                        class="alert d-none">
                    </div>


                    <h2
                        id="balanceAmount"
                        class="mb-4">

                        در حال دریافت...

                    </h2>



                    <form id="balanceForm">


                        <div class="mb-3">


                            <label
                                class="form-label">

                                تغییر موجودی

                            </label>


                            <div class="input-group" dir="ltr">
                                <span class="input-group-text">تومان</span>
                                <input
                                    type="text"
                                    inputmode="decimal"
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
