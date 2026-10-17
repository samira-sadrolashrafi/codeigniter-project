<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_report_model extends CI_Model
{
    /**
     * Return daily income, expense, and historical balance data.
     *
     * Chart data:
     * - labels: dates
     * - income: daily income totals
     * - expense: daily expense totals
     * - balance: cumulative historical balance
     */
    public function get_cashflow_report($user_id, $from = null, $to = null)
    {
        $user_id = filter_var(
            $user_id,
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1))
        );

        if ($user_id === false) {
            return false;
        }

        // Get active transactions grouped by their financial date.
        $transactions = $this->db->query(
            "SELECT
                t.transaction_date AS report_date,
                COALESCE(
                    SUM(
                        CASE
                            WHEN c.type = 'income' THEN t.amount
                            ELSE 0
                        END
                    ),
                    0.00
                ) AS income,
                COALESCE(
                    SUM(
                        CASE
                            WHEN c.type = 'expense' THEN t.amount
                            ELSE 0
                        END
                    ),
                    0.00
                ) AS expense
             FROM transactions AS t
             INNER JOIN categories AS c
                ON c.id = t.category_id
             WHERE t.user_id = ?
               AND t.deleted_at IS NULL
             GROUP BY t.transaction_date
             ORDER BY t.transaction_date ASC",
            array($user_id)
        );

        if ($transactions === false) {
            return false;
        }

        // Sum history deltas for each financial date.
        $history = $this->db->query(
            "SELECT
                effective_date AS report_date,
                SUM(amount_delta) AS amount_delta
             FROM balance_history
             WHERE user_id = ?
             GROUP BY effective_date
             ORDER BY effective_date ASC",
            array($user_id)
        );

        if ($history === false) {
            return false;
        }

        $daily = array();

        // Initialize a date's values if needed.
        $ensure_date = function ($date) use (&$daily) {
            if (!isset($daily[$date])) {
                $daily[$date] = array(
                    'income_cents' => 0,
                    'expense_cents' => 0,
                    'delta_cents' => 0
                );
            }
        };

        // Add daily income and expense totals.
        foreach ($transactions->result() as $row) {
            $date = $row->report_date;

            $income_cents = $this->decimal_to_cents($row->income);
            $expense_cents = $this->decimal_to_cents($row->expense);

            if (
                $income_cents === false ||
                $expense_cents === false
            ) {
                return false;
            }

            $ensure_date($date);

            $daily[$date]['income_cents'] = $income_cents;
            $daily[$date]['expense_cents'] = $expense_cents;
        }

        // Add the net historical balance change for each date.
        foreach ($history->result() as $row) {
            $date = $row->report_date;
            $delta_cents = $this->decimal_to_cents(
                $row->amount_delta
            );

            if ($delta_cents === false) {
                return false;
            }

            $ensure_date($date);

            $daily[$date]['delta_cents'] = $delta_cents;
        }

        // Process dates chronologically.
        ksort($daily, SORT_STRING);

        $labels = array();
        $income = array();
        $expense = array();
        $balance = array();
        $period_income_cents = 0;
        $period_expense_cents = 0;

        // The initial balance is zero.
        $running_balance_cents = 0;

        foreach ($daily as $date => $values) {
            // Build cumulative balance across the entire history first.
            // Otherwise the first balance point in a filtered report is wrong.
            $running_balance_cents += $values['delta_cents'];

            if (($from !== null && $date < $from) ||
                ($to !== null && $date > $to)) {
                continue;
            }

            $period_income_cents += $values['income_cents'];
            $period_expense_cents += $values['expense_cents'];

            $labels[] = $date;
            $income[] = $this->cents_to_chart_number(
                $values['income_cents']
            );
            $expense[] = $this->cents_to_chart_number(
                $values['expense_cents']
            );
            $balance[] = $this->cents_to_chart_number(
                $running_balance_cents
            );
        }

        return array(
            'labels' => $labels,
            'income' => $income,
            'expense' => $expense,
            'balance' => $balance,
            'totals' => array(
                'income' => $this->cents_to_decimal($period_income_cents),
                'expense' => $this->cents_to_decimal($period_expense_cents)
            )
        );
    }

    /**
     * Convert a decimal amount to integer cents without float arithmetic.
     */
    private function decimal_to_cents($value)
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

        // Keep the calculation within PHP's integer range.
        if (strlen($integer) > 16) {
            return false;
        }

        $fraction = isset($parts[1]) ? $parts[1] : '';
        $fraction = str_pad($fraction, 2, '0');

        $cents = ((int) $integer * 100) + (int) $fraction;

        if ($negative && $cents !== 0) {
            return -$cents;
        }

        return $cents;
    }

    /**
     * Return the precise amount for KPI display without float conversion.
     */
    private function cents_to_decimal($cents)
    {
        $negative = $cents < 0;
        $absolute = abs($cents);
        return ($negative ? '-' : '')
            . intdiv($absolute, 100)
            . '.'
            . str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Convert integer cents to the numeric format expected by Chart.js.
     */
    private function cents_to_chart_number($cents)
    {
        return round($cents / 100, 2);
    }
}