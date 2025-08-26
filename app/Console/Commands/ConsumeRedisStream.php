<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ConsumeRedisStream extends Command
{
    protected $signature = 'redis:consume-stream';
    protected $description = 'Consume Redis Stream and store entries in the database';

    public function handle(): void
    {
        $stream = 'user_action_audit';

        while (true) {
            $redis = Redis::connection();

            $raw = $redis->executeRaw(['XREAD', 'COUNT', '10', 'BLOCK', '5000', 'STREAMS', $stream, '0']);
            // XREAD with BLOCK to wait max 5 seconds for new entries

            if (empty($raw)) {
                continue;
            }

            $entries = $raw[0][1] ?? [];

            foreach ($entries as $entry) {
                $id = $entry[0];
                $fieldsArray = $entry[1];

                $fields = collect($fieldsArray)
                    ->chunk(2)
                    ->filter(function ($pair) {
                        /**
                         * Redis stream fields are stored as key-value pairs,
                         * so we filter out any pairs that do not have exactly two elements.
                         */
                        return count($pair) === 2;
                    })
                    ->mapWithKeys(function ($pair) {
                        $values = $pair->values(); // Reset keys first because Redis stream fields are not guaranteed to be sequential
                        return [$values[0] => $values[1]];
                    })
                    ->all();

                try {
                    AuditLog::create([
                        'level'     => $fields['level']     ?? 'info',
                        'trace_id'  => $fields['trace_id']  ?? null,
                        'action'    => $fields['action']    ?? null,
                        'system'    => $fields['system']    ?? null,
                        'user_id'   => $fields['user_id']   ?? null,
                        'user_type' => $fields['user_type'] ?? null,
                        'message'   => $fields['message']   ?? null,
                        'data'      => $fields['data']      ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Remove (acknowledge) the message from the stream
                    $redis->executeRaw(['XDEL', $stream, $id]);

                    $this->info("Processed and deleted entry ID $id");
                } catch (\Exception $e) {
                    $this->error("Failed to store entry $id: " . $e->getMessage());
                }
            }
        }
    }
}
