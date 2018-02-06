<?php

namespace App\Http\Controllers;

use \Exception;
use App\Clients\BrokerNode;
use App\DataMap;
use App\UploadSession;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Tuupola\Trytes;

class UploadSessionController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    
    
    public static $ChunkEventsRecord  = null;
    
    
    private static function initEventRecord()
    {
        if (is_null(self::$ChunkEventsRecord)) {
            self::$ChunkEventsRecord = new ChunkEvents();
        }
    }
    
    
    
    
    
    public function store(Request $request)
    {
        $genesis_hash = $request->input('genesis_hash');
        $file_size_bytes = $request->input('file_size_bytes');
        // $beta_brokernode_ip = $request->input('beta_brokernode_ip');

        // TODO: Handle PRL Payments.

        // // Starts session with beta.
        // try {
        //     $beta_session =
        //         self::startSessionBeta($genesis_hash, $file_size_bytes, $beta_brokernode_ip);
        // } catch (Exception $e) {
        //     return response("Error: Beta start session failed: {$e}", 500);
        // }

        // Starts session on self (alpha).
        $upload_session =
            self::startSession($genesis_hash, $file_size_bytes);

        // Appends beta_session_id for client.
        $res = clone $upload_session;
        // $res['beta_session_id'] = $beta_session["id"];

        return response()->json($res);
    }

    private static function startSessionBeta($genesis_hash, $file_size_bytes, $beta_brokernode_ip) {
        $beta_broker_path = "{$beta_brokernode_ip}/api/v1/upload-sessions/beta";
        if (!filter_var($beta_broker_path, FILTER_VALIDATE_URL)) {
            return response("Error: Invalid Beta IP {$beta_brokernode_ip}", 422);
        }

        // Starts session with beta.
        $http_client = new Client();
        $res = $http_client->post($beta_broker_path, [
            'form_params' => [
                'genesis_hash' => $genesis_hash,
                'file_size_bytes' => $file_size_bytes
            ]
        ]);
        $beta_session = json_decode($res->getBody(), true);
        return $beta_session;
    }

    /**
     * Store a newly created resource in storage. This is for the beta session
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function storeBeta(Request $request)
    {
        $genesis_hash = $request->input('genesis_hash');
        $file_size_bytes = $request->input('file_size_bytes');

        $upload_session =
            self::startSession($genesis_hash, $file_size_bytes, "beta");

        return response()->json($upload_session);
    }
    
    
    public function reportChunkFinished(Request $request)
    {
        $chunk_id = $request->input('chunk_id');
        $hook_id = $request->input('hook_id');
        
        self::initEventRecord();
        self::$ChunkEventsRecord->addHookNodeFinishedChunkEvent($hooknode['ip_address']);
        
        
        
        return response()->json($upload_session);
    }
    

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $session = UploadSession::find($id);
        if (empty($session)) return response('Session not found.', 404);

        $genesis_hash = $session['genesis_hash'];
        $chunk = $request->input('chunk');

        $data_map = DataMap::where('genesis_hash', $genesis_hash)
            ->where('chunk_idx', $chunk['idx'])
            ->first();

        // Error Responses
        if (empty($data_map)) return response('Datamap not found', 404);

        // Convert hash to trytes to be used as an address.
        $trytes = new Trytes(["characters" => Trytes::IOTA]);
        $hash_in_tryte_format = $trytes->encode($data_map["hash"]);
        $shortened_hash = substr($hash_in_tryte_format, 0, 81);

        // Save address and message on data_map
        $data_map->address = $shortened_hash;
        $data_map->message = $chunk["data"];
        $data_map->save();

        switch($data_map->processChunk()) {
            case 'already_attached':
                return response('Chunk already attached.', 204);
            case 'hooknode_unavailable':
                return response('Processing: Hooknodes are busy', 102);
            case 'success':
                return response('Success.', 204);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $session = UploadSession::find($id);
        if (empty($session)) return response('Session not found.', 404);

        // TODO: More auth checking? Maybe also send the genesis_hash?

        DataMap::where('genesis_hash', $session['genesis_hash'])->delete();
        $session->delete();

        return response('Success.', 204);
    }

    /**
     * Gets the status of a chunk. This will be polled until status  is complete
     * or error.
     *
     * @param  int $body => { genesis_hash, chunk: { idx, hash } }
     * @return \Illuminate\Http\Response
     */
    public function chunkStatus(Request $request)
    {
        // TODO: Make it middleware to find datamap and check hashes match.
        // It is shared by a few functions.

        $genesis_hash = $request->input('genesis_hash');
        $chunk_idx = $request->input('chunk_idx');

        $data_map = DataMap::where('genesis_hash', $genesis_hash)
            ->where('chunk_idx', $chunk_idx)
            ->first();

        // Error Responses
        if (empty($data_map)) return response('Datamap not found', 404);


        // Don't need to check tangle if already detected to be complete.
        if ($data_map['status'] == DataMap::status['complete']) {
            return response()->json(['status' => $data_map['status']]);
        }

        // Check tangle. This should be done in the background.
        $isAttached = !BrokerNode::dataNeedsAttaching($request);
        if ($isAttached) {
            // Saving to DB is not needed yet, but will be once we check
            // status on the tangle in the background.
            $data_map['status'] = DataMap::status['complete'];
            $data_map->save();
        }

        return response()->json(['status' => $data_map['status']]);
    }

    public function brokerNodeListener(Request $request)
    {
        $cmd = $request->input('command');
        $resAddress = "{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']}";
        // This is a hack to cast an associative array to an object.
        // I don't know how to use PHP properly :(
        $req = (object)[
            "command" => $cmd,
            "responseAddress" => $resAddress,
            "message" => $request->input('message'),
            "chunkId" => $request->input('chunkId'),
            "address" => "WHLOOOOOOAAAAAAAAAAAAAAAAALAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA",
        ];

        try {
            switch ($cmd) {
                case 'processNewChunk':
                    BrokerNode::processNewChunk($request);
                    return response('Success.', 204);

                case 'verifyTx':
                    $dataIsAttached = !BrokerNode::dataNeedsAttaching($request);
                    // TODO: Figure out what client expects.
                    return response()->json(["dataIsAttached" => $dataIsAttached]);

                default:
                    return response("Unrecognized command: {$cmd}", 404);
            }
        } catch (Exception $e) {
            return response("Internal Server Error: {$e->getMessage()}", 500);
        }

    }

    /**
     * Private
     */

    private static function startSession(
        $genesis_hash, $file_size_bytes, $type="alpha"
    ) {
        // TODO: Make 500 an env variable.
        $file_chunk_count = ceil($file_size_bytes / 500);
        // This could take a while, but if we make this async, we have a race
        // condition if the client attempts to upload before broker-node
        // can save to DB.
        DataMap::buildMap($genesis_hash, $file_chunk_count);

        return UploadSession::firstOrCreate([
            'type' => $type,
            'genesis_hash' => $genesis_hash,
            'file_size_bytes' => $file_size_bytes
        ]);

        return response()->json($upload_session);
    }
}
