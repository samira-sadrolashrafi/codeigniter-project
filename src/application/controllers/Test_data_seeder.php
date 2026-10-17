
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/Transaction_service.php';

class Test_data_seeder extends CI_Controller
{
    private $transaction_service;

    private $category_definitions = array(
        'income' => array(
            'تست - حقوق',
            'تست - درآمد پروژه',
            'تست - هدیه',
            'تست - درآمد متفرقه'
        ),
        'expense' => array(
            'تست - خوراک',
            'تست - حمل‌ونقل',
            'تست - خرید',
            'تست - قبض و خدمات',
            'تست - سلامت',
            'تست - تفریح'
        )
    );

    public function __construct()
    {
        parent::__construct();

        // Load Carbon installed manually under application/third_party.
        $carbon_autoload = APPPATH
            . 'third_party/Carbon/autoload.php';

        if (is_file($carbon_autoload)) {
            require_once $carbon_autoload;
        }

        $this->load->model('User_model');
        $this->load->model('Category_model');
        $this->load->model('Balance_model');

        $this->transaction_service = new Transaction_service();
    }

    /**
     * CLI only.
     *
     * Usage:
     * php index.php test_data_seeder run USER_ID COUNT
     */
    public function run($user_id = null, $count = 100)
    {
        if (!is_cli()) {
            show_404();
            return;
        }

        if (!class_exists('Carbon\Carbon')) {
            echo "ERROR: Carbon could not be loaded.\n";
            echo "Check application/third_party/Carbon/autoload.php\n";
            return;
        }

        $user_id = filter_var(
            $user_id,
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1))
        );

        $count = filter_var(
            $count,
            FILTER_VALIDATE_INT,
            array('options' => array(
                'min_range' => 1,
                'max_range' => 500
            ))
        );

        if ($user_id === false || $count === false) {
            echo "Usage:\n";
            echo "php index.php test_data_seeder run USER_ID COUNT\n";
            echo "COUNT must be between 1 and 500.\n";
            return;
        }

        // Never create test transactions for an unknown or inactive user.
        if (!$this->CI_user_is_active($user_id)) {
            echo "ERROR: Active user not found.\n";
            return;
        }

        // The application must already have an initialized balance.
        if (!$this->Balance_model->find_by_user_id($user_id)) {
            echo "ERROR: User balance has not been initialized.\n";
            return;
        }

        echo "Preparing test categories...\n";

        $category_ids = array(
            'income' => array(),
            'expense' => array()
        );

        foreach ($this->category_definitions as $type => $names) {
            foreach ($names as $name) {
                $category_id = $this->get_or_create_category(
                    $user_id,
                    $name,
                    $type
                );

                if ($category_id === false) {
                    echo "ERROR: Could not prepare category: ";
                    echo $name . "\n";
                    return;
                }

                $category_ids[$type][] = $category_id;
            }
        }

        // Carbon date range: six calendar months ago through today.
        $start_date = \Carbon\Carbon::today()
            ->subMonths(6)
            ->startOfDay();

        $end_date = \Carbon\Carbon::today()->endOfDay();

        $total_days = (int) $start_date->diffInDays($end_date);

        $created = 0;
        $income_count = 0;
        $expense_count = 0;

        echo "\nStarting test data generation...\n";
        echo "User ID: {$user_id}\n";
        echo "Requested transactions: {$count}\n";
        echo "Date range: " . $start_date->toDateString();
        echo " to " . $end_date->toDateString() . "\n\n";

        for ($i = 1; $i <= $count; $i++) {
            // Keep both income and expense data in the generated sample.
            $type = random_int(1, 100) <= 35
                ? 'income'
                : 'expense';

            $category_index = random_int(
                0,
                count($category_ids[$type]) - 1
            );

            $category_id = $category_ids[$type][$category_index];

            $category_name = $this->category_definitions[$type]
                [$category_index];

            $transaction_date = $start_date
                ->copy()
                ->addDays(random_int(0, $total_days))
                ->toDateString();

            // Amounts are generated as integer cents to avoid floats.
            // Income: 1,000,000 to 5,000,000.
            // Expense: 20,000 to 1,500,000.
            if ($type === 'income') {
                $amount_cents = random_int(
                    100000000,
                    500000000
                );

                $income_count++;
            } else {
                $amount_cents = random_int(
                    2000000,
                    150000000
                );

                $expense_count++;
            }

            $amount = $this->format_amount($amount_cents);

            $values = array(
                'title' => sprintf(
                    'داده آزمایشی #%03d - %s',
                    $i,
                    $category_name
                ),
                'amount' => $amount,
                'category_id' => $category_id,
                'transaction_date' => $transaction_date,
                'description' => 'تراکنش آزمایشی برای بررسی نمودار'
            );

            // Use the actual application logic for each transaction.
            $transaction_id = $this->transaction_service->create(
                $user_id,
                $values
            );

            if ($transaction_id === false) {
                echo "\nERROR: Failed to create transaction #{$i}.\n";
                echo "Transactions created successfully: {$created}\n";
                echo "Income: {$income_count}\n";
                echo "Expense: {$expense_count}\n";
                echo "Earlier successful inserts remain saved.\n";
                return;
            }

            $created++;

            if ($created % 10 === 0) {
                echo "Created {$created} / {$count}\n";
            }
        }

        echo "\n====================================\n";
        echo "TEST DATA GENERATION COMPLETED\n";
        echo "====================================\n";
        echo "Transactions created: {$created}\n";
        echo "Income transactions: {$income_count}\n";
        echo "Expense transactions: {$expense_count}\n";
        echo "Categories prepared: 10\n";
        echo "User ID: {$user_id}\n";
        echo "====================================\n";
    }

    /**
     * Check whether the user exists and is active.
     */
    private function CI_user_is_active($user_id)
    {
        return (bool) $this->User_model->find_active_by_id($user_id);
    }

    /**
     * Reuse an active category with the expected name and type,
     * or create it if it does not exist.
     */
    private function get_or_create_category($user_id, $name, $type)
    {
        $matches = $this->Category_model
            ->find_categories_by_name($user_id, $name);

        foreach ($matches as $category) {
            if ($category->type !== $type) {
                echo "ERROR: Category has an unexpected type: ";
                echo $name . "\n";
                return false;
            }

            if ($category->deleted_at !== null) {
                echo "ERROR: Test category is deleted: ";
                echo $name . "\n";
                echo "Restore it before running the seeder.\n";
                return false;
            }

            return (int) $category->id;
        }

        $now = date('Y-m-d H:i:s');

        $category_id = $this->Category_model->create(array(
            'user_id' => $user_id,
            'name' => $name,
            'type' => $type,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
            'restore_until' => null
        ));

        return $category_id ? (int) $category_id : false;
    }

    /**
     * Convert integer cents to a DECIMAL-compatible string.
     */
    private function format_amount($cents)
    {
        return intdiv($cents, 100)
            . '.'
            . str_pad(
                (string) ($cents % 100),
                2,
                '0',
                STR_PAD_LEFT
            );
    }
}
