<?php

namespace App\Console\Commands;

use App\Clients\BrokerNode;
use App\DataMap;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;

class CheckChunkStatus extends Command
{
    const HOOKNODE_TIMEOUT_THRESHOLD_MINUTES = 20;

    protected $signature = 'CheckChunkStatus:checkStatus';
    protected $description =
        'Polls the status of chunks that have been sent to hook nodes';

    /**
     * Execute the console command.
     */
    public static function handle()
    {
        $thresholdTime = Carbon::now()
            ->subMinutes(self::HOOKNODE_TIMEOUT_THRESHOLD_MINUTES)
            ->toDateTimeString();

        // self::processUnassignedChunks();
        self::updateUnverifiedDatamaps($thresholdTime);
        self::updateTimedoutDatamaps($thresholdTime);
        self::purgeCompletedSessions();
    }

    /**
     * Private
     * */

    private static function processUnassignedChunks()
    {
        $unassigned_datamaps = DataMap::getUnassigned()->all();
        foreach ($unassigned_datamaps as &$dmap) { // TODO: Concurrent.
            $dmap->processChunk();
        }
    }

    private static function updateUnverifiedDatamaps($thresholdTime)
    {
        $datamaps_unverified =
            DataMap::where('status', DataMap::status['unverified'])
                ->where('updated_at', '>=', $thresholdTime)
                ->get();

        $attached_datamaps = array_filter($datamaps_unverified->toArray(), function ($dmap) {
            // TODO: Make these concurrent.
            $req = (object)[
                "address" => $dmap["address"],
                "message" => $dmap["message"],
                "trunkTransaction" => $dmap["trunkTransaction"],
                "branchTransaction" => $dmap["branchTransaction"],
                "chunk_idx" => $dmap["chunk_idx"],
                "hooknode_id" => $dmap["hooknode_id"],
            ];

            $chunkResult = BrokerNode::verifyChunkMessagesMatchRecord($req);
            $is_attached = count($chunkResult->matchesTangle) != 0;

            //$is_attached = !BrokerNode::dataNeedsAttaching($req);
            /*
             * replace with 'verifyChunksMatchRecord' if we also want to check
             * branch and trunk match the record.
             *
             * verifyChunkMessagesMatchRecord and verifyChunksMatchRecord both
             * check tangle for the address and makes sure the message matches,
             * verifyChunksMatchRecord also checks trunk and branch.
             *
             * TODO:  BrokerNode::verifyChunkMessagesMatchRecord($req) in this file does
             * not currently take advantage of the fact that you can pass an array
             * to BrokerNode::verifyChunkMessagesMatchRecord.  Modify this
             */
            return $is_attached;
        });

        unset($datamaps_unverified); // Purges unused memory.

        $attached_ids = array_map(function ($dmap) {
            return $dmap["id"];
        }, $attached_datamaps);

        // Mass Update DB.
        DataMap::whereIn('id', $attached_ids)
            ->update(['status' => DataMap::status['complete']]);

        self::incrementHooknodeReputations($attached_datamaps);
    }

    private static function updateTimedoutDatamaps($thresholdTime)
    {
        $datamaps_timedout_query =
            DataMap::where('status', DataMap::status['unverified'])
                ->where('updated_at', '<', $thresholdTime);
        $datamaps_timedout = $datamaps_timedout_query->get();

        self::decrementHooknodeReputations($datamaps_timedout);

        // TODO: Retry with another hooknode.

        // NOT FOR TESTNET.
        // $datamaps_timedout_query->update([
        //     'status' => DataMap::status['unassigned'],
        //     'hooknode_id' => null,
        //     'branchTransaction' => null,
        //     'trunkTransaction' => null,
        // ]);
        // // Note: DB and in memory model are now out of sync, but it should be ok...
        // foreach ($datamaps_timedout as &$dmap) { // TODO: Concurrent.
        //     $dmap->processChunk();
        // }
    }

    private static function incrementHooknodeReputations($datamaps) {
        // TODO: Increment hooknode reputations for $datamaps.
        return true; // placeholder.
    }

    private static function decrementHooknodeReputations($datamaps) {
        // TODO: Increment hooknode reputations for $datamaps.
        return true; // placeholder.
    }

    public static function purgeCompletedSessions()
    {
        $not_complete_gen_hash = DB::table('data_maps')
            ->where('status', '<>', DataMap::status['complete'])
            ->select('genesis_hash', DB::raw('COUNT(genesis_hash) as not_completed'))
            ->groupBy('genesis_hash')
            ->pluck('genesis_hash');

        $completed_gen_hash = DB::table('upload_sessions')
            ->whereNotIn('genesis_hash', $not_complete_gen_hash)
            ->pluck('genesis_hash');

        DB::transaction(function () use ($completed_gen_hash) {
            DB::table('data_maps')
                ->whereIn('genesis_hash', $completed_gen_hash)
                ->delete();

            DB::table('upload_sessions')
                ->whereIn('genesis_hash', $completed_gen_hash)
                ->delete();
        });
    }
}
