<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Models\Config;
use App\Services\Subscribe;
use function base64_encode;
use function json_decode;
use function json_encode;
use const PHP_EOL;

final class V2Ray extends Base
{
    public function getContent($user): string
    {
        $links = '';
        //判断是否开启V2Ray订阅
        if (! Config::obtain('enable_v2_sub')) {
            return $links;
        }

        $nodes_raw = Subscribe::getSubNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $node_custom_config = json_decode($node_raw->custom_config, true);

            if ((int) $node_raw->sort === 11) {
                $v2_port = $node_custom_config['offset_port_user'] ?? ($node_custom_config['offset_port_node'] ?? 443);
                $security = $node_custom_config['security'] ?? 'none';
                $network = $node_custom_config['network'] ?? '';
                $header = $node_custom_config['header'] ?? ['type' => 'none'];
                $header_type = $header['type'] ?? '';
                $host = $node_custom_config['header']['request']['headers']['Host'][0] ??
                    $node_custom_config['host'] ?? '';
                $path = $node_custom_config['header']['request']['path'][0] ?? $node_custom_config['path'] ?? '/';

                $relay_server = $node_custom_config['relay_server'];
                //add
                if (($node_custom_config['enable_vless'] ?? '0') === '1') {
                    $enable_reality = $node_custom_config['enable_reality'];
                    if ($enable_reality) {
                        $client_fingerprint = $node_custom_config['client_fingerprint'] ?? '';
                        $public_key = $node_custom_config['public_key'] ?? '';
                        $flow = $node_custom_config['flow'] ?? '';
                        $reality_shortid = $node_custom_config['reality_shortid'] ?? '';
                        $links .= 'vless://' . $user->uuid . '@' .
                            ($relay_server ?? $node_raw->server) . ':' . $v2_port .
                            '?encryption=none' .
                            '&flow=' . $flow .
                            '&security=' . $security .
                            '&sni=' . $host .
                            '&fp=' . $client_fingerprint .
                            '&pbk=' . $public_key .
                            '&sid=' . $reality_shortid .
                            '&type=' . $network .
                            '&headerType=' . $header_type .
                            '#' . $node_raw->name . PHP_EOL;
                    }   
                } else {
                    $v2rayn_array = [
                        'v' => '2',
                        'ps' => $node_raw->name,
                        'add' => ($relay_server ?? $node_raw->server),
                        'port' => $v2_port,
                        'id' => $user->uuid,
                        'aid' => 0,
                        'net' => $network,
                        'type' => $header_type,
                        'host' => $host,
                        'path' => $path,
                        'tls' => $security,
                    ];
    
                    $links .=  'vmess://' . base64_encode(json_encode($v2rayn_array)) . PHP_EOL;
                }
            }
        }

        return $links;
    }
}

//   vless://81905a5f-30bf-48ce-b2e3-1bfc58c80dba@80.251.208.72:31233?encryption=none&flow=xtls-rprx-vision&security=reality&sni=www.smzdm.com&fp=chrome&pbk=Tkn--526y7NXdw92jR_MuZDN8YXWuDGsEk0LboKKWSM&sid=22&type=tcp&headerType=none#tttt

