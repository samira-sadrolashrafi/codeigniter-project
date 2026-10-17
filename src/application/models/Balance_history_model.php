<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Balance_history_model extends CI_Model
{
    private $table = 'balance_history';

    /**
     * Insert a new balance history event.
     *
     * Returns the inserted ID on success, or false on failure.
     */
    public function create_event($data)
    {
        if (!is_array($data) || !isset(
                $data['user_id'],
                $data['event_type'],
                $data['amount_delta'],
                $data['effective_date']
            )
        ) {
            return false;
        }

        // Validate user ID.
        $user_id = filter_var(
            $data['user_id'],
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1))
        );

        if (
            $user_id === false ||
            !is_string($data['event_type'])
        ) {
            return false;
        }

        // Validate event type without maintaining a fixed whitelist.
        $event_type = trim($data['event_type']);

        if ($event_type === '' || strlen($event_type) > 40) {
            return false;
        }

        // Normalize the signed balance change.
        $amount_delta = $this->normalize_decimal(
            $data['amount_delta']
        );

        if ($amount_delta === false) {
            return false;
        }

        // Validate the effective financial date.
        $effective_date = $data['effective_date'];

        if (
            !is_string($effective_date) ||
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $effective_date)
        ) {
            return false;
        }

        $date = DateTime::createFromFormat(
            '!Y-m-d',
            $effective_date
        );

        if (
            !$date ||
            $date->format('Y-m-d') !== $effective_date
        ) {
            return false;
        }

        // Transaction ID is optional for manual balance operations.
        $transaction_id = null;

        if (
            array_key_exists('transaction_id', $data) &&
            $data['transaction_id'] !== null
        ) {
            $transaction_id = filter_var(
                $data['transaction_id'],
                FILTER_VALIDATE_INT,
                array('options' => array('min_range' => 1))
            );

            if ($transaction_id === false) {
                return false;
            }
        }

        // Normalize an optional target balance.
        $target_balance = null;

        if (
            array_key_exists('target_balance', $data) &&
            $data['target_balance'] !== null
        ) {
            $target_balance = $this->normalize_decimal(
                $data['target_balance']
            );

            if ($target_balance === false) {
                return false;
            }
        }

        // Opening-balance and reset events require a target balance.
        if (
            in_array(
                $event_type,
                array('opening_balance', 'balance_reset'),
                true
            ) &&
            $target_balance === null
        ) {
            return false;
        }

        // Description is optional.
        $description = null;

        if (
            array_key_exists('description', $data) &&
            $data['description'] !== null
        ) {
            if (
                !is_string($data['description']) ||
                strlen($data['description']) > 255
            ) {
                return false;
            }

            $description = $data['description'];
        }

        // Prepare the history record.
        $record = array(
            'user_id' => $user_id,
            'transaction_id' => $transaction_id,
            'event_type' => $event_type,
            'amount_delta' => $amount_delta,
            'target_balance' => $target_balance,
            'effective_date' => $effective_date,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s')
        );

        if (!$this->db->insert($this->table, $record)) {
            return false;
        }

        return (int) $this->db->insert_id();
    }

    /**
     * Normalize a signed decimal without using floating-point arithmetic.
     * DECIMAL(15,2): maximum 13 integer digits and 2 decimal digits.
     */
    private function normalize_decimal($value)
    {
        if (!is_string($value) && !is_int($value)) {
            return false;
        }

        $value = trim((string) $value);

        if (!preg_match('/^-?\d+(?:\.\d{1,2})?$/', $value)) {
            return false;
        }

        $negative = isset($value[0]) && $value[0] === '-';
        $unsigned = $negative ? substr($value, 1) : $value;

        $parts = explode('.', $unsigned, 2);

        $integer = ltrim($parts[0], '0');
        $integer = $integer === '' ? '0' : $integer;

        if (strlen($integer) > 13) {
            return false;
        }

        $fraction = isset($parts[1]) ? $parts[1] : '';
        $fraction = str_pad($fraction, 2, '0');

        $normalized = $integer . '.' . $fraction;

        if ($negative && preg_match('/[1-9]/', $unsigned)) {
            return '-' . $normalized;
        }

        return $normalized;
    }
}