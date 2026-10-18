<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/Balance_service.php';

class Transaction_service
{
    protected $CI;
    private $balance_service;
    private $restore_days = 30;

    public function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->load->model('User_model');
        $this->CI->load->model('Category_model');
        $this->CI->load->model('Transaction_model');
        $this->CI->load->model('Balance_history_model');

        $this->balance_service = new Balance_service();
    }


    private function get_history_delta($type, $amount, $reverse = false)
    {
        if ($type !== 'income' && $type !== 'expense') {
            return false;
        }

        $amount = $this->balance_service->normalize_amount($amount);

        if ($amount === false) {
            return false;
        }

        $is_negative = ($type === 'expense');

        if ($reverse) {
            $is_negative = !$is_negative;
        }

        if ($amount === '0.00') {
            return '0.00';
        }

        return $is_negative ? '-' . $amount : $amount;
    }


    private function record_history_event(
        $user_id,
        $event_type,
        $effect_type,
        $amount,
        $effective_date,
        $transaction_id,
        $reverse = false
    ) {
        $amount_delta = $this->get_history_delta(
            $effect_type,
            $amount,
            $reverse
        );

        if ($amount_delta === false) {
            return false;
        }

        $event_id = $this->CI->Balance_history_model->create_event(array(
            'user_id' => $user_id,
            'transaction_id' => $transaction_id,
            'event_type' => $event_type,
            'amount_delta' => $amount_delta,
            'effective_date' => $effective_date
        ));

        return $event_id !== false;
    }

    private function validate_data($data)
    {
        if (
            !is_array($data) ||
            !isset(
                $data['title'],
                $data['amount'],
                $data['category_id'],
                $data['transaction_date']
            ) ||
            !is_string($data['title']) ||
            (
                !is_string($data['category_id']) &&
                !is_int($data['category_id'])
            )
        ) {
            return false;
        }

        $title = trim($data['title']);

        $category_id = filter_var(
            $data['category_id'],
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1))
        );

        if (
            preg_match('/^.{1,150}$/u', $title) !== 1 ||
            $category_id === false
        ) {
            return false;
        }

        $amount = $this->balance_service->normalize_amount(
            $data['amount']
        );

        if (
            $amount === false ||
            !is_string($data['transaction_date']) ||
            !preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $data['transaction_date']
            )
        ) {
            return false;
        }

        $date = DateTime::createFromFormat(
            '!Y-m-d',
            $data['transaction_date']
        );

        if (
            !$date ||
            $date->format('Y-m-d') !== $data['transaction_date']
        ) {
            return false;
        }

        $description = $data['description'] ?? null;

        if ($description !== null && !is_string($description)) {
            return false;
        }

        return array(
            'title' => $title,
            'amount' => $amount,
            'category_id' => $category_id,
            'transaction_date' => $data['transaction_date'],
            'description' => $description === '' ? null : $description
        );
    }

    public function create($user_id, $data)
    {
        $values = $this->validate_data($data);

        if ($values === false) {
            return false;
        }

        $db = $this->CI->db;
        $db->trans_begin();

        if (!$this->CI->User_model->lock_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }

        $category = $this->CI->Category_model
            ->find_category_by_id_with_lock(
                $user_id,
                $values['category_id']
            );

        if (!$category || $category->deleted_at !== null) {
            $db->trans_rollback();
            return false;
        }

        $values['user_id'] = $user_id;
        $values['created_at'] = date('Y-m-d H:i:s');

        $id = $this->CI->Transaction_model->create($values);

        if (!$id ||
            !$this->balance_service->apply_transaction_effect(
                $user_id,
                $category->type,
                $values['amount']
            ) ||
            !$this->record_history_event(
                $user_id,
                $category->type,
                $category->type,
                $values['amount'],
                $values['transaction_date'],
                $id
            ) ||
            $db->trans_status() === false
        ) {
            $db->trans_rollback();
            return false;
        }

        return $db->trans_commit() ? $id : false;
    }

    public function get($user_id, $transaction_id)
    {
        if (!$this->CI->User_model->find_active_by_id($user_id)) {
            return false;
        }

        $transaction = $this->CI->Transaction_model
            ->find_transaction_by_id($user_id, $transaction_id);

        return (
            $transaction &&
            $transaction->deleted_at === null
        ) ? $transaction : false;
    }

    /**
     * Get active transactions with optional server-side pagination.
     *
     * When $page is null, preserve the previous behavior and return
     * all active transactions for compatibility with the current frontend.
     */
    public function get_all($user_id, $page = null, $per_page = 10)
    {
        if (!$this->CI->User_model->find_active_by_id($user_id)) {
            return false;
        }

        $total = $this->CI->Transaction_model
            ->count_active_transactions_by_user_id($user_id);

        if ($total === false) {
            return false;
        }

        if ($page === null) {
            // Preserve the previous behavior until the frontend sends page.
            $transactions = $this->CI->Transaction_model
                ->find_active_transactions_by_user_id($user_id);

            $current_page = 1;
            $effective_per_page = max(1, $total);
            $total_pages = $total > 0 ? 1 : 0;
        } else {
            $page = max(1, (int) $page);
            $per_page = max(1, min(100, (int) $per_page));

            $offset = ($page - 1) * $per_page;

            $transactions = $this->CI->Transaction_model
                ->find_active_transactions_by_user_id(
                    $user_id,
                    $per_page,
                    $offset
                );

            $current_page = $page;
            $effective_per_page = $per_page;
            $total_pages = (int) ceil($total / $per_page);
        }

        if ($transactions === false) {
            return false;
        }

        return array(
            'transactions' => $transactions,
            'pagination' => array(
                'current_page' => $current_page,
                'per_page' => $effective_per_page,
                'total_items' => $total,
                'total_pages' => $total_pages
            )
        );
    }

    /**
     * Get deleted transactions with optional server-side pagination.
     *
     * When $page is null, preserve the previous behavior and return
     * all deleted transactions for compatibility with the current frontend.
     */
    public function get_deleted($user_id, $page = null, $per_page = 10)
    {
        if (!$this->CI->User_model->find_active_by_id($user_id)) {
            return false;
        }

        $total = $this->CI->Transaction_model
            ->count_deleted_transactions_by_user_id($user_id);

        if ($total === false) {
            return false;
        }

        if ($page === null) {
            $transactions = $this->CI->Transaction_model
                ->find_deleted_transactions_by_user_id($user_id);

            $current_page = 1;
            $effective_per_page = max(1, $total);
            $total_pages = $total > 0 ? 1 : 0;
        } else {
            $page = max(1, (int) $page);
            $per_page = max(1, min(100, (int) $per_page));

            $offset = ($page - 1) * $per_page;

            $transactions = $this->CI->Transaction_model
                ->find_deleted_transactions_by_user_id(
                    $user_id,
                    $per_page,
                    $offset
                );

            $current_page = $page;
            $effective_per_page = $per_page;
            $total_pages = (int) ceil($total / $per_page);
        }

        if ($transactions === false) {
            return false;
        }

        return array(
            'transactions' => $transactions,
            'pagination' => array(
                'current_page' => $current_page,
                'per_page' => $effective_per_page,
                'total_items' => $total,
                'total_pages' => $total_pages
            )
        );
    }

    public function update($user_id, $transaction_id, $data)
    {
        $values = $this->validate_data($data);

        if ($values === false) {
            return false;
        }

        $db = $this->CI->db;
        $db->trans_begin();

        if (!$this->CI->User_model->lock_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }

        $old = $this->CI->Transaction_model
            ->find_transaction_by_id_with_lock(
                $user_id,
                $transaction_id
            );

        if (!$old || $old->deleted_at !== null) {
            $db->trans_rollback();
            return false;
        }

        $old_category = $this->CI->Category_model
            ->find_category_by_id_with_lock(
                $user_id,
                $old->category_id
            );

        $new_category = $this->CI->Category_model
            ->find_category_by_id_with_lock(
                $user_id,
                $values['category_id']
            );

        if (
            !$old_category ||
            !$new_category ||
            $new_category->deleted_at !== null
        ) {
            $db->trans_rollback();
            return false;
        }

        $values['updated_at'] = date('Y-m-d H:i:s');

        if (
            !$this->balance_service->reverse_transaction_effect(
                $user_id,
                $old_category->type,
                $old->amount
            ) ||
            !$this->CI->Transaction_model
                ->update_active_transaction_by_user(
                    $user_id,
                    $transaction_id,
                    $values
                ) ||
            !$this->balance_service->apply_transaction_effect(
                $user_id,
                $new_category->type,
                $values['amount']
            ) ||
            !$this->record_history_event(
                $user_id,
                'transaction_edit_reversal',
                $old_category->type,
                $old->amount,
                $old->transaction_date,
                $transaction_id,
                true
            ) ||
            !$this->record_history_event(
                $user_id,
                'transaction_edit',
                $new_category->type,
                $values['amount'],
                $values['transaction_date'],
                $transaction_id
            ) ||
            $db->trans_status() === false
        ) {
            $db->trans_rollback();
            return false;
        }

        return $db->trans_commit();
    }

    public function delete($user_id, $transaction_id)
    {
        $db = $this->CI->db;
        $db->trans_begin();

        if (!$this->CI->User_model->lock_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }

        $transaction = $this->CI->Transaction_model
            ->find_transaction_by_id_with_lock(
                $user_id,
                $transaction_id
            );

        if (!$transaction || $transaction->deleted_at !== null) {
            $db->trans_rollback();
            return false;
        }

        $category = $this->CI->Category_model
            ->find_category_by_id_with_lock(
                $user_id,
                $transaction->category_id
            );

        if (!$category) {
            $db->trans_rollback();
            return false;
        }

        $now = date('Y-m-d H:i:s');

        $until = date(
            'Y-m-d H:i:s',
            time() + $this->restore_days * 86400
        );

        if (
            !$this->CI->Transaction_model
                ->soft_delete_transaction_by_user(
                    $user_id,
                    $transaction_id,
                    $now,
                    $until
                ) ||
            !$this->balance_service->reverse_transaction_effect(
                $user_id,
                $category->type,
                $transaction->amount
            ) ||
            !$this->record_history_event(
                $user_id,
                'transaction_delete_reversal',
                $category->type,
                $transaction->amount,
                $transaction->transaction_date,
                $transaction_id,
                true
            ) ||
            $db->trans_status() === false
        ) {
            $db->trans_rollback();
            return false;
        }

        return $db->trans_commit();
    }

    public function restore($user_id, $transaction_id)
    {
        $db = $this->CI->db;
        $db->trans_begin();

        if (!$this->CI->User_model->lock_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }

        $transaction = $this->CI->Transaction_model
            ->find_transaction_by_id_with_lock(
                $user_id,
                $transaction_id
            );

        if (
            !$transaction ||
            !$transaction->deleted_at ||
            !$transaction->restore_until ||
            strtotime($transaction->restore_until) <= time()
        ) {
            $db->trans_rollback();
            return false;
        }

        $category = $this->CI->Category_model
            ->find_category_by_id_with_lock(
                $user_id,
                $transaction->category_id
            );

        if (!$category || $category->deleted_at !== null) {
            $db->trans_rollback();
            return false;
        }

        if (
            !$this->CI->Transaction_model
                ->restore_transaction_by_user(
                    $user_id,
                    $transaction_id
                ) ||
            !$this->balance_service->apply_transaction_effect(
                $user_id,
                $category->type,
                $transaction->amount
            ) ||
            !$this->record_history_event(
                $user_id,
                'transaction_restore',
                $category->type,
                $transaction->amount,
                $transaction->transaction_date,
                $transaction_id
            ) ||
            $db->trans_status() === false
        ) {
            $db->trans_rollback();
            return false;
        }

        return $db->trans_commit();
    }
}
