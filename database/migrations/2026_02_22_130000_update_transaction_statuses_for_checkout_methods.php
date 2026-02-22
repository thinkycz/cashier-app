<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement(<<<'SQL'
                CREATE TABLE transactions_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    transaction_id VARCHAR NOT NULL UNIQUE,
                    customer_id INTEGER NULL,
                    subtotal NUMERIC NOT NULL,
                    discount NUMERIC NOT NULL DEFAULT 0,
                    total NUMERIC NOT NULL,
                    status VARCHAR NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'cash', 'card', 'order')),
                    notes TEXT NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
                )
            SQL);

            DB::statement(<<<'SQL'
                INSERT INTO transactions_new (id, transaction_id, customer_id, subtotal, discount, total, status, notes, created_at, updated_at)
                SELECT
                    id,
                    transaction_id,
                    customer_id,
                    subtotal,
                    discount,
                    total,
                    CASE
                        WHEN status = 'completed' THEN 'cash'
                        WHEN status = 'cancelled' THEN 'order'
                        ELSE status
                    END,
                    notes,
                    created_at,
                    updated_at
                FROM transactions
            SQL);

            DB::statement('DROP TABLE transactions');
            DB::statement('ALTER TABLE transactions_new RENAME TO transactions');
            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        if ($driver === 'mysql') {
            DB::statement("UPDATE transactions SET status = 'cash' WHERE status = 'completed'");
            DB::statement("UPDATE transactions SET status = 'order' WHERE status = 'cancelled'");
            DB::statement("ALTER TABLE transactions MODIFY status ENUM('open', 'cash', 'card', 'order') NOT NULL DEFAULT 'open'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement(<<<'SQL'
                CREATE TABLE transactions_old (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    transaction_id VARCHAR NOT NULL UNIQUE,
                    customer_id INTEGER NULL,
                    subtotal NUMERIC NOT NULL,
                    discount NUMERIC NOT NULL DEFAULT 0,
                    total NUMERIC NOT NULL,
                    status VARCHAR NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'completed', 'cancelled')),
                    notes TEXT NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
                )
            SQL);

            DB::statement(<<<'SQL'
                INSERT INTO transactions_old (id, transaction_id, customer_id, subtotal, discount, total, status, notes, created_at, updated_at)
                SELECT
                    id,
                    transaction_id,
                    customer_id,
                    subtotal,
                    discount,
                    total,
                    CASE
                        WHEN status IN ('cash', 'card', 'order') THEN 'completed'
                        ELSE status
                    END,
                    notes,
                    created_at,
                    updated_at
                FROM transactions
            SQL);

            DB::statement('DROP TABLE transactions');
            DB::statement('ALTER TABLE transactions_old RENAME TO transactions');
            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        if ($driver === 'mysql') {
            DB::statement("UPDATE transactions SET status = 'completed' WHERE status IN ('cash', 'card', 'order')");
            DB::statement("ALTER TABLE transactions MODIFY status ENUM('open', 'completed', 'cancelled') NOT NULL DEFAULT 'open'");
        }
    }
};
