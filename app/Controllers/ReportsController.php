<?php

declare(strict_types=1);

class ReportsController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function summary(): void
    {
        [$start, $end] = $this->getDateRange();

        $stmt = $this->db->prepare(
            'SELECT
                COALESCE(SUM(CASE WHEN type = "income" THEN amount END), 0) AS income,
                COALESCE(SUM(CASE WHEN type = "expense" THEN amount END), 0) AS expense
             FROM transactions
             WHERE date >= :start AND date <= :end'
        );
        $stmt->execute(['start' => $start, 'end' => $end]);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['income' => 0, 'expense' => 0];

        $income = (float) $totals['income'];
        $expense = (float) $totals['expense'];

        $this->jsonResponse([
            'start' => $start,
            'end' => $end,
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ]);
    }

    public function expenseByCategory(): void
    {
        [$start, $end] = $this->getDateRange();

        $stmt = $this->db->prepare(
            'SELECT category, COALESCE(SUM(amount), 0) AS total
             FROM transactions
             WHERE type = "expense" AND date >= :start AND date <= :end
             GROUP BY category
             ORDER BY total DESC'
        );
        $stmt->execute(['start' => $start, 'end' => $end]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->jsonResponse([
            'start' => $start,
            'end' => $end,
            'items' => array_map(
                static fn (array $row): array => [
                    'category' => (string) $row['category'],
                    'total' => (float) $row['total'],
                ],
                $rows
            ),
        ]);
    }

    public function dynamics(): void
    {
        [$start, $end] = $this->getDateRange();

        $stmt = $this->db->prepare(
            'SELECT date,
                COALESCE(SUM(CASE WHEN type = "income" THEN amount END), 0) AS income,
                COALESCE(SUM(CASE WHEN type = "expense" THEN amount END), 0) AS expense
             FROM transactions
             WHERE date >= :start AND date <= :end
             GROUP BY date
             ORDER BY date'
        );
        $stmt->execute(['start' => $start, 'end' => $end]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $labels = [];
        $incomeSeries = [];
        $expenseSeries = [];

        foreach ($rows as $row) {
            $labels[] = (string) $row['date'];
            $incomeSeries[] = (float) $row['income'];
            $expenseSeries[] = (float) $row['expense'];
        }

        $this->jsonResponse([
            'start' => $start,
            'end' => $end,
            'labels' => $labels,
            'income' => $incomeSeries,
            'expense' => $expenseSeries,
        ]);
    }

    private function jsonResponse(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function getDateRange(): array
    {
        $startParam = isset($_GET['start']) ? (string) $_GET['start'] : '';
        $endParam = isset($_GET['end']) ? (string) $_GET['end'] : '';

        $start = $this->normalizeDate($startParam) ?? (new DateTimeImmutable('first day of this month'))->format('Y-m-d');
        $end = $this->normalizeDate($endParam) ?? (new DateTimeImmutable('last day of this month'))->format('Y-m-d');

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    private function normalizeDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($date === false) {
            return null;
        }

        return $date->format('Y-m-d');
    }
}
