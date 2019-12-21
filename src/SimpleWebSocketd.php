<?php declare(strict_types=1);
/**
 * SwooleHttpd
 * From this time, you never be alone~
 */
namespace SwooleHttpd;

use Swoole\ExitException;
use Swoole\Http\Request;
use Swoole\WebSocket\Server as Websocket_Server;

trait SimpleWebSocketd
{
    public $websocket_open_handler = null;
    public $websocket_handler = null;
    public $websocket_exception_handler = null;
    public $websocket_close_handler = null;
    
    public function onOpen(Websocket_Server $server, Request $request)
    {
        SwooleContext::G()->initHttp($request, null);
        if (!$this->websocket_open_handler) {
            return;
        }
        ($this->websocket_open_handler)();
    }
    public function onMessage($server, $frame)
    {
        $InitObLevel = ob_get_level();
        SwooleContext::G()->initWebSocket($frame);
        
        $fd = $frame->fd;
        ob_start(
            function ($str) use ($server,$fd) {
                if ('' === $str) {
                    return;
                }
                $server->push($fd, $str);
            }
        );
        try {
            if ($frame->opcode != 0x08 || !$this->websocket_close_handler) {
                ($this->websocket_handler)();
            } else {
                ($this->websocket_close_handler)();
            }
        } catch (\Throwable $ex) {
            if (!($ex instanceof ExitException)) {
                ($this->websocket_exception_handler)($ex);
            }
        }
        for ($i = ob_get_level();$i > $InitObLevel;$i--) {
            ob_end_flush();
        }
    }
}
