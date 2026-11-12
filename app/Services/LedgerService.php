<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\Party;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LedgerService
{
    public function post(int $partyId, ?int $factoryId, string $account, string $date, string $description, string $source, int $sourceId, float $debit, float $credit): void
    {
        if (!in_array($account, ['receivable', 'payable'], true) || $debit < 0 || $credit < 0 || ($debit > 0 && $credit > 0)) {
            throw ValidationException::withMessages(['amount' => 'Invalid ledger posting.']);
        }
        LedgerEntry::create([
            'party_id' => $partyId, 'factory_id' => $factoryId, 'account_type' => $account,
            'entry_on' => $date, 'description' => $description, 'source_type' => $source,
            'source_id' => $sourceId, 'debit' => round($debit, 2), 'credit' => round($credit, 2),
        ]);
    }

    public function balances(Party $party): array
    {
        $sums = $party->ledgerEntries()->selectRaw('account_type, SUM(debit) as dr, SUM(credit) as cr')->groupBy('account_type')->get()->keyBy('account_type');
        $rec = (float)$party->opening_receivable + (float)($sums->get('receivable')?->dr ?? 0) - (float)($sums->get('receivable')?->cr ?? 0);
        $pay = (float)$party->opening_payable + (float)($sums->get('payable')?->cr ?? 0) - (float)($sums->get('payable')?->dr ?? 0);
        return ['receivable' => round($rec, 2), 'payable' => round($pay, 2)];
    }

    public function timeline(Party $party, string $account): array
    {
        $balance = (float)($account === 'receivable' ? $party->opening_receivable : $party->opening_payable);
        $entries = $party->ledgerEntries()->with('factory')->where('account_type', $account)
            ->orderBy('entry_on')->orderBy('id')->get();
        $rows = [];
        foreach ($entries as $entry) {
            $balance += $account === 'receivable'
                ? ((float)$entry->debit - (float)$entry->credit)
                : ((float)$entry->credit - (float)$entry->debit);
            $rows[] = ['entry' => $entry, 'balance' => round($balance, 2)];
        }
        return ['opening' => (float)($account === 'receivable' ? $party->opening_receivable : $party->opening_payable), 'rows' => $rows, 'closing' => round($balance, 2)];
    }
}
