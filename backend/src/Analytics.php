<?php
declare(strict_types=1);

final class Analytics
{
    private const EVENTS = [
        'visit', 'page_view', 'content_view', 'engaged_view', 'page_engagement', 'wa_click',
        'hero_cta_click', 'calculator_submit', 'contact_submit', 'web_vital', 'client_error',
    ];

    public static function record(array $body): void
    {
        $type = (string) ($body['type'] ?? '');
        if (!in_array($type, self::EVENTS, true) || self::isBot()) return;

        $windowStarted = (int) ($_SESSION['analytics_window_started'] ?? 0);
        if ($windowStarted === 0 || time() - $windowStarted >= 60) {
            $_SESSION['analytics_window_started'] = time();
            $_SESSION['analytics_window_count'] = 0;
        }
        $_SESSION['analytics_window_count'] = (int) ($_SESSION['analytics_window_count'] ?? 0) + 1;
        if ($_SESSION['analytics_window_count'] > 120) return;

        $path = self::pagePath((string) ($body['page'] ?? '/'));
        $eventId = self::uuid((string) ($body['event_id'] ?? ''));
        if ($eventId === '') {
            $rateKey = 'last_stat_' . hash('sha256', $type . '|' . $path);
            if ((time() - (int) ($_SESSION[$rateKey] ?? 0)) < (in_array($type, ['page_view', 'visit'], true) ? 2 : 1)) return;
            $_SESSION[$rateKey] = time();
        }

        [$contentType, $contentSlug] = self::contentIdentity($path);
        $client = self::clientInfo((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $visitorHash = self::anonymousHash((string) ($body['visitor_id'] ?? ''), true);
        $sessionHash = self::anonymousHash((string) ($body['session_id'] ?? ''), false);
        $source = self::referrerSource((string) ($body['referrer'] ?? ''));
        $values = [
            'event_id' => $eventId !== '' ? $eventId : null,
            'type' => $type,
            'page_path' => $path,
            'content_type' => $contentType,
            'content_slug' => $contentSlug,
            'visitor_hash' => $visitorHash,
            'session_hash' => $sessionHash,
            'device_type' => $client['device'],
            'os_family' => $client['os'],
            'browser_family' => $client['browser'],
            'referrer_source' => $source,
            'screen_bucket' => self::screenBucket((int) ($body['screen_width'] ?? 0)),
            'landing_path' => self::pagePath((string) ($body['landing_path'] ?? $path)),
            'utm_source' => self::plain((string) ($body['utm_source'] ?? ''), 100),
            'utm_medium' => self::plain((string) ($body['utm_medium'] ?? ''), 100),
            'utm_campaign' => self::plain((string) ($body['utm_campaign'] ?? ''), 150),
            'utm_content' => self::plain((string) ($body['utm_content'] ?? ''), 150),
            'cta_id' => self::plain((string) ($body['cta_id'] ?? ''), 100),
            'event_label' => self::plain((string) ($body['event_label'] ?? ''), 120),
            'engagement_ms' => max(0, min(86400000, (int) ($body['engagement_ms'] ?? 0))),
            'scroll_depth' => max(0, min(100, (int) ($body['scroll_depth'] ?? 0))),
            'metric_name' => in_array((string) ($body['metric_name'] ?? ''), ['LCP', 'INP', 'CLS'], true) ? (string) $body['metric_name'] : '',
            'metric_value' => isset($body['metric_value']) && is_numeric($body['metric_value']) ? max(0, min(1000000, (float) $body['metric_value'])) : null,
        ];

        $db = Database::connection();
        try {
            if (!self::hasAdvancedSchema()) {
                self::recordLegacy($db, $values);
                return;
            }
            $db->beginTransaction();
            $columns = array_keys($values);
            $statement = $db->prepare('INSERT IGNORE INTO stats (' . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')');
            $statement->execute($values);
            if ($statement->rowCount() === 0) {
                $db->rollBack();
                return;
            }
            self::updateSession($db, $values);
            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            AppLogger::exception($error);
        }
        if (random_int(1, 200) === 1) self::prune();
    }

    public static function report(int $days = 30): array
    {
        $days = max(7, min(365, $days));
        $db = Database::connection();
        if (!self::hasDimensions()) return self::legacyReport($db, $days);

        [$start, $end, $previousStart] = self::periodBounds($days);
        $summary = self::summary($db, $start, $end);
        $previous = self::summary($db, $previousStart, $start);
        $changes = [];
        foreach (['page_views', 'visitors', 'sessions', 'engaged_sessions', 'converted_sessions', 'wa_clicks', 'engagement_rate', 'conversion_rate'] as $key) {
            $changes[$key] = self::percentChange((float) ($summary[$key] ?? 0), (float) ($previous[$key] ?? 0));
        }

        return [
            'days' => $days, 'timezone' => 'Asia/Jakarta', 'retention_days' => self::retentionDays(),
            'migration_required' => !self::hasAdvancedSchema(), 'summary' => $summary,
            'previous_summary' => $previous, 'changes' => $changes,
            'daily' => self::daily($db, $start, $end),
            'devices' => self::grouped($db, 'device_type', $start, $end, 5),
            'operating_systems' => self::grouped($db, 'os_family', $start, $end, 8),
            'browsers' => self::grouped($db, 'browser_family', $start, $end, 8),
            'sources' => self::grouped($db, 'referrer_source', $start, $end, 10),
            'screens' => self::grouped($db, 'screen_bucket', $start, $end, 8),
            'pages' => self::topPages($db, $start, $end),
            'whatsapp_pages' => self::topWhatsAppPages($db, $start, $end),
            'visitor_types' => self::visitorTypes($db, $start, $end),
            'funnel' => self::funnel($db, $start, $end),
            'campaigns' => self::hasAdvancedSchema() ? self::campaigns($db, $start, $end) : [],
            'ctas' => self::hasAdvancedSchema() ? self::ctas($db, $start, $end) : [],
            'web_vitals' => self::hasAdvancedSchema() ? self::webVitals($db, $start, $end) : [],
            'data_quality' => self::dataQuality($db, $start, $end),
        ];
    }

    public static function clientInfo(string $userAgent): array
    {
        $ua = strtolower($userAgent);
        if (preg_match('/bot|crawler|spider|slurp|headless|lighthouse/', $ua)) return ['device' => 'unknown', 'os' => 'Bot', 'browser' => 'Bot'];
        $device = preg_match('/ipad|tablet|kindle|silk/', $ua) || (str_contains($ua, 'android') && !str_contains($ua, 'mobile'))
            ? 'tablet' : (preg_match('/mobile|iphone|ipod|android/', $ua) ? 'mobile' : 'desktop');
        $os = match (true) {
            str_contains($ua, 'android') => 'Android', preg_match('/iphone|ipad|ipod/', $ua) === 1 => 'iOS/iPadOS',
            str_contains($ua, 'windows') => 'Windows', str_contains($ua, 'cros') => 'ChromeOS',
            str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') => 'macOS', str_contains($ua, 'linux') => 'Linux', default => 'Lainnya',
        };
        $browser = match (true) {
            str_contains($ua, 'edg/') || str_contains($ua, 'edgios') => 'Edge', str_contains($ua, 'opr/') || str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'samsungbrowser') => 'Samsung Internet', str_contains($ua, 'firefox') || str_contains($ua, 'fxios') => 'Firefox',
            str_contains($ua, 'crios') || str_contains($ua, 'chrome') => 'Chrome', str_contains($ua, 'safari') => 'Safari', default => 'Lainnya',
        };
        return compact('device', 'os', 'browser');
    }

    public static function webVitalSummary(int $days = 7): array
    {
        if (!self::hasAdvancedSchema()) return [];
        [$start, $end] = self::periodBounds(max(1, min(30, $days)));
        return self::webVitals(Database::connection(), $start, $end);
    }

    public static function prune(): int
    {
        $db = Database::connection();
        $cutoff = gmdate('Y-m-d H:i:s', time() - self::retentionDays() * 86400);
        $statement = $db->prepare('DELETE FROM stats WHERE created_at < :cutoff');
        $statement->execute(['cutoff' => $cutoff]);
        $deleted = $statement->rowCount();
        foreach (['admin_sessions' => 'last_seen_at', 'system_health_snapshots' => 'checked_at'] as $table => $column) {
            try {
                $old = $db->prepare("DELETE FROM {$table} WHERE {$column} < :cutoff");
                $old->execute(['cutoff' => $cutoff]);
                $deleted += $old->rowCount();
            } catch (Throwable) {}
        }
        return $deleted;
    }

    private static function updateSession(PDO $db, array $v): void
    {
        $now = gmdate('Y-m-d H:i:s');
        $newSession = $db->prepare(
            'INSERT IGNORE INTO analytics_sessions
             (session_hash,visitor_hash,started_at,last_seen_at,landing_path,referrer_source,utm_source,utm_medium,utm_campaign,device_type)
             VALUES (:session,:visitor,:started_at,:last_seen_at,:landing,:source,:utm_source,:utm_medium,:utm_campaign,:device)'
        );
        $newSession->execute([
            'session' => $v['session_hash'], 'visitor' => $v['visitor_hash'], 'started_at' => $now, 'last_seen_at' => $now,
            'landing' => $v['landing_path'], 'source' => $v['referrer_source'], 'utm_source' => $v['utm_source'],
            'utm_medium' => $v['utm_medium'], 'utm_campaign' => $v['utm_campaign'], 'device' => $v['device_type'],
        ]);
        $isNewSession = $newSession->rowCount() > 0 ? 1 : 0;
        $pageView = in_array($v['type'], ['visit', 'page_view'], true) ? 1 : 0;
        $session = $db->prepare(
            'UPDATE analytics_sessions SET last_seen_at=:now,page_views=page_views+:page_view,event_count=event_count+1,
             is_engaged=GREATEST(is_engaged,:engaged),converted_at=COALESCE(converted_at,:converted) WHERE session_hash=:session'
        );
        $session->execute(['now' => $now, 'page_view' => $pageView, 'engaged' => $v['type'] === 'engaged_view' ? 1 : 0, 'converted' => $v['type'] === 'wa_click' ? $now : null, 'session' => $v['session_hash']]);
        $visitor = $db->prepare(
             'INSERT INTO analytics_visitors (visitor_hash,first_seen_at,last_seen_at,total_sessions,total_page_views)
             VALUES (:visitor,:first_seen_at,:last_seen_at,:sessions,:views) ON DUPLICATE KEY UPDATE last_seen_at=VALUES(last_seen_at),
             total_sessions=total_sessions+:sessions2,total_page_views=total_page_views+:views2'
        );
        $visitor->execute(['visitor' => $v['visitor_hash'], 'first_seen_at' => $now, 'last_seen_at' => $now, 'sessions' => $isNewSession, 'views' => $pageView, 'sessions2' => $isNewSession, 'views2' => $pageView]);
    }

    private static function recordLegacy(PDO $db, array $v): void
    {
        if ($v['type'] === 'page_view') $v['type'] = 'visit';
        if (!in_array($v['type'], ['visit', 'wa_click'], true)) return;
        try {
            $statement = $db->prepare('INSERT INTO stats (type,page_path,content_type,content_slug,visitor_hash,session_hash,device_type,os_family,browser_family,referrer_source,screen_bucket) VALUES (:type,:page_path,:content_type,:content_slug,:visitor_hash,:session_hash,:device_type,:os_family,:browser_family,:referrer_source,:screen_bucket)');
            $statement->execute(array_intersect_key($v, array_flip(['type','page_path','content_type','content_slug','visitor_hash','session_hash','device_type','os_family','browser_family','referrer_source','screen_bucket'])));
        } catch (Throwable $error) { AppLogger::exception($error); }
    }

    private static function summary(PDO $db, string $start, string $end): array
    {
        // Produksi dapat berada dalam kondisi migrasi parsial (misalnya tabel sesi
        // sudah terbentuk, tetapi kolom engagement_ms belum ditambahkan). Jangan
        // biarkan seluruh laporan gagal hanya karena satu metrik lanjutan belum siap.
        $averageEngagement = self::hasColumn('stats', 'engagement_ms')
            ? "AVG(IF(type='page_engagement',engagement_ms,NULL))"
            : 'NULL';
        $statement = $db->prepare("SELECT SUM(type IN ('visit','page_view')) page_views,SUM(type='wa_click') wa_clicks,
            COUNT(DISTINCT NULLIF(visitor_hash,'')) visitors,
            COUNT(DISTINCT NULLIF(session_hash,'')) sessions,
            COUNT(DISTINCT IF(type='engaged_view',NULLIF(session_hash,''),NULL)) engaged_sessions,
            COUNT(DISTINCT IF(type='wa_click',NULLIF(session_hash,''),NULL)) converted_sessions,
            {$averageEngagement} average_engagement_ms
            FROM stats WHERE created_at>=:start AND created_at<:end");
        $statement->execute(compact('start', 'end')); $row = $statement->fetch() ?: []; $result = [];
        foreach (['page_views','wa_clicks','visitors','sessions','engaged_sessions','converted_sessions'] as $key) $result[$key] = (int) ($row[$key] ?? 0);
        $result['average_engagement_seconds'] = round(((float) ($row['average_engagement_ms'] ?? 0)) / 1000, 1);
        $result['engagement_rate'] = $result['sessions'] ? round(100 * $result['engaged_sessions'] / $result['sessions'], 1) : 0.0;
        $result['conversion_rate'] = $result['sessions'] ? round(100 * $result['converted_sessions'] / $result['sessions'], 1) : 0.0;
        return $result;
    }

    private static function grouped(PDO $db, string $column, string $start, string $end, int $limit): array
    {
        if (!in_array($column, ['device_type','os_family','browser_family','referrer_source','screen_bucket'], true)) return [];
        $s = $db->prepare("SELECT {$column} label,COUNT(*) total FROM stats WHERE type IN ('visit','page_view') AND created_at>=:start AND created_at<:end GROUP BY {$column} ORDER BY total DESC LIMIT {$limit}");
        $s->execute(compact('start','end')); return self::integerRows($s->fetchAll());
    }

    private static function daily(PDO $db, string $start, string $end): array
    {
        $s = $db->prepare("SELECT DATE(CONVERT_TZ(created_at,'+00:00','+07:00')) label,SUM(type IN ('visit','page_view')) total,SUM(type='wa_click') secondary FROM stats WHERE created_at>=:start AND created_at<:end GROUP BY label ORDER BY label");
        $s->execute(compact('start','end')); return array_map(static fn($r) => ['label'=>(string)$r['label'],'total'=>(int)$r['total'],'secondary'=>(int)$r['secondary']], $s->fetchAll());
    }

    private static function topPages(PDO $db, string $start, string $end): array
    {
        $s = $db->prepare("SELECT page_path label,SUM(type IN ('visit','page_view')) total,
            COUNT(DISTINCT IF(type IN ('visit','page_view'),session_hash,NULL)) sessions,
            COUNT(DISTINCT IF(type='wa_click',session_hash,NULL)) converted_sessions
            FROM stats WHERE created_at>=:start AND created_at<:end GROUP BY page_path HAVING total>0 ORDER BY total DESC LIMIT 12");
        $s->execute(compact('start','end')); return array_map(static function($r): array {
            $sessions=(int)$r['sessions'];$converted=(int)$r['converted_sessions'];
            return ['label'=>(string)$r['label'],'total'=>(int)$r['total'],'sessions'=>$sessions,'converted_sessions'=>$converted,'conversion_rate'=>$sessions?round(100*$converted/$sessions,1):0.0];
        }, $s->fetchAll());
    }

    private static function topWhatsAppPages(PDO $db, string $start, string $end): array
    {
        $s = $db->prepare("SELECT page_path label,COUNT(*) total FROM stats WHERE type='wa_click' AND created_at>=:start AND created_at<:end GROUP BY page_path ORDER BY total DESC LIMIT 10");
        $s->execute(compact('start','end')); return self::integerRows($s->fetchAll());
    }

    private static function visitorTypes(PDO $db, string $start, string $end): array
    {
        if (self::tableExists('analytics_visitors')) {
            $s = $db->prepare('SELECT SUM(first_seen_at>=:start AND first_seen_at<:end) new_visitors,SUM(first_seen_at<:start2 AND last_seen_at>=:start3 AND last_seen_at<:end2) returning_visitors FROM analytics_visitors');
            $s->execute(['start'=>$start,'end'=>$end,'start2'=>$start,'start3'=>$start,'end2'=>$end]);
        } else {
            $s = $db->prepare("SELECT SUM(first_seen>=:start AND first_seen<:end) new_visitors,SUM(first_seen<:start2 AND last_seen>=:start3 AND last_seen<:end2) returning_visitors FROM (SELECT visitor_hash,MIN(created_at) first_seen,MAX(created_at) last_seen FROM stats WHERE visitor_hash<>'' GROUP BY visitor_hash) v");
            $s->execute(['start'=>$start,'end'=>$end,'start2'=>$start,'start3'=>$start,'end2'=>$end]);
        }
        $r = $s->fetch() ?: [];
        return [['label'=>'Pengunjung baru','total'=>(int)($r['new_visitors']??0)],['label'=>'Pengunjung kembali','total'=>(int)($r['returning_visitors']??0)]];
    }

    private static function funnel(PDO $db, string $start, string $end): array
    {
        $s = $db->prepare("SELECT COUNT(DISTINCT session_hash) sessions,
            COUNT(DISTINCT IF(type='content_view',session_hash,NULL)) content_views,
            COUNT(DISTINCT IF(type='engaged_view',session_hash,NULL)) engaged,
            COUNT(DISTINCT IF(type='wa_click',session_hash,NULL)) converted FROM stats WHERE created_at>=:start AND created_at<:end");
        $s->execute(compact('start','end')); $r=$s->fetch()?:[];
        return [['label'=>'Sesi','total'=>(int)($r['sessions']??0)],['label'=>'Lihat konten','total'=>(int)($r['content_views']??0)],['label'=>'Terlibat','total'=>(int)($r['engaged']??0)],['label'=>'Klik WhatsApp','total'=>(int)($r['converted']??0)]];
    }

    private static function campaigns(PDO $db, string $start, string $end): array
    {
        $s=$db->prepare("SELECT CONCAT(IF(utm_source='',referrer_source,utm_source),IF(utm_campaign='', '',CONCAT(' / ',utm_campaign))) label,
            COUNT(DISTINCT IF(type IN ('visit','page_view'),session_hash,NULL)) total,
            COUNT(DISTINCT IF(type='wa_click',session_hash,NULL)) converted_sessions
            FROM stats WHERE created_at>=:start AND created_at<:end GROUP BY label HAVING total>0 ORDER BY total DESC LIMIT 12");
        $s->execute(compact('start','end')); return array_map(static function($r): array {$total=(int)$r['total'];$converted=(int)$r['converted_sessions'];return ['label'=>(string)$r['label'],'total'=>$total,'converted_sessions'=>$converted,'conversion_rate'=>$total?round(100*$converted/$total,1):0.0];},$s->fetchAll());
    }

    private static function ctas(PDO $db, string $start, string $end): array
    {
        $s=$db->prepare("SELECT IF(cta_id='',type,cta_id) label,COUNT(*) total FROM stats WHERE type IN ('wa_click','hero_cta_click','calculator_submit','contact_submit') AND created_at>=:start AND created_at<:end GROUP BY label ORDER BY total DESC LIMIT 12");
        $s->execute(compact('start','end')); return self::integerRows($s->fetchAll());
    }

    private static function webVitals(PDO $db, string $start, string $end): array
    {
        $s=$db->prepare("SELECT metric_name,device_type,metric_value FROM stats WHERE type='web_vital' AND metric_name<>'' AND metric_value IS NOT NULL AND created_at>=:start AND created_at<:end ORDER BY metric_value");
        $s->execute(compact('start','end')); $groups=[];
        foreach ($s->fetchAll() as $r) $groups[$r['device_type']][$r['metric_name']][]=(float)$r['metric_value'];
        $out=[];
        foreach ($groups as $device=>$metrics) foreach ($metrics as $name=>$values) {
            $index=max(0,(int)ceil(count($values)*.75)-1); $value=$values[$index]; $threshold=['LCP'=>2500,'INP'=>200,'CLS'=>.1][$name]??0;
            $out[]=['device'=>$device,'metric'=>$name,'p75'=>round($value,3),'samples'=>count($values),'status'=>$value<=$threshold?'good':'needs_attention'];
        }
        return $out;
    }

    private static function dataQuality(PDO $db, string $start, string $end): array
    {
        $advanced=self::hasAdvancedSchema(); $eventId=$advanced?'SUM(event_id IS NOT NULL)':'0';
        $s=$db->prepare("SELECT COUNT(*) events,{$eventId} identified,SUM(device_type='unknown') unknown_devices,MAX(created_at) last_event_at FROM stats WHERE created_at>=:start AND created_at<:end");
        $s->execute(compact('start','end')); $r=$s->fetch()?:[]; $total=max(1,(int)($r['events']??0));
        return ['last_event_at'=>$r['last_event_at']??null,'event_id_coverage'=>round(100*(int)($r['identified']??0)/$total,1),'unknown_device_rate'=>round(100*(int)($r['unknown_devices']??0)/$total,1),'advanced_schema'=>$advanced];
    }

    private static function periodBounds(int $days): array
    {
        $tz=new DateTimeZone('Asia/Jakarta'); $end=(new DateTimeImmutable('tomorrow',$tz))->setTimezone(new DateTimeZone('UTC')); $start=$end->modify("-{$days} days");
        return [$start->format('Y-m-d H:i:s'),$end->format('Y-m-d H:i:s'),$start->modify("-{$days} days")->format('Y-m-d H:i:s')];
    }

    private static function legacyReport(PDO $db, int $days): array
    {
        $rows=$db->query('SELECT type,COUNT(*) total FROM stats GROUP BY type')->fetchAll(); $summary=['page_views'=>0,'wa_clicks'=>0,'visitors'=>0,'sessions'=>0,'engaged_sessions'=>0,'converted_sessions'=>0,'average_engagement_seconds'=>0.0,'engagement_rate'=>0.0,'conversion_rate'=>0.0];
        foreach($rows as $r){if($r['type']==='visit')$summary['page_views']=(int)$r['total'];if($r['type']==='wa_click')$summary['wa_clicks']=(int)$r['total'];}
        return ['days'=>$days,'retention_days'=>self::retentionDays(),'migration_required'=>true,'summary'=>$summary,'previous_summary'=>$summary,'changes'=>[],'daily'=>[],'devices'=>[],'operating_systems'=>[],'browsers'=>[],'sources'=>[],'screens'=>[],'pages'=>[],'whatsapp_pages'=>[],'visitor_types'=>[],'funnel'=>[],'campaigns'=>[],'ctas'=>[],'web_vitals'=>[],'data_quality'=>['advanced_schema'=>false]];
    }

    private static function integerRows(array $rows): array { return array_map(static fn($r)=>['label'=>(string)($r['label']?:'Tidak diketahui'),'total'=>(int)$r['total']],$rows); }
    private static function percentChange(float $current,float $previous): ?float { return $previous==0.0?($current==0.0?0.0:null):round(100*($current-$previous)/$previous,1); }
    private static function uuid(string $v): string { return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',$v)?strtolower($v):''; }
    private static function plain(string $v,int $max): string { return mb_substr(trim((string)preg_replace('/[\x00-\x1F\x7F]/u','',$v)),0,$max); }
    private static function anonymousHash(string $id,bool $visitor): string { $id=preg_replace('/[^a-zA-Z0-9._-]/','',$id)??'';if(strlen($id)<16||strlen($id)>100)$id=(string)($_SERVER['REMOTE_ADDR']??'unknown').'|'.(string)($_SERVER['HTTP_USER_AGENT']??'').($visitor?'':'|'.gmdate('Y-m-d-H'));$key=Config::get('ANALYTICS_HASH_KEY',Config::get('APP_URL','').'|'.Config::get('DB_NAME','ddu'));return hash_hmac('sha256',($visitor?'visitor|':'session|').$id,$key); }
    private static function pagePath(string $v): string { $p=parse_url($v,PHP_URL_PATH)?:'/';$p='/'.ltrim((string)$p,'/');return preg_match('#^/[a-zA-Z0-9/_.-]*$#',$p)?mb_substr($p,0,255):'/'; }
    private static function contentIdentity(string $p): array { if(preg_match('#^/artikel/([a-z0-9]+(?:-[a-z0-9]+)*)/?$#',$p,$m))return['article',$m[1]];$reserved=['admin','api','uploads','about.html','index.html','transparansi.html','kebijakan-privasi.html'];if(preg_match('#^/([a-z0-9]+(?:-[a-z0-9]+)*)/?$#',$p,$m)&&!in_array($m[1],$reserved,true))return['program',$m[1]];return['page','']; }
    private static function referrerSource(string $r): string { if($r==='')return'Langsung';$h=strtolower((string)parse_url($r,PHP_URL_HOST));$own=strtolower((string)parse_url(Config::get('APP_URL',''),PHP_URL_HOST));if($h===''||$h===$own||($own!==''&&str_ends_with($h,'.'.$own)))return'Internal';return match(true){str_contains($h,'google.')=>'Google',str_contains($h,'instagram.')=>'Instagram',str_contains($h,'facebook.')||$h==='fb.com'=>'Facebook',str_contains($h,'tiktok.')=>'TikTok',str_contains($h,'youtube.')||$h==='youtu.be'=>'YouTube',str_contains($h,'whatsapp.')||$h==='wa.me'=>'WhatsApp',str_contains($h,'bing.')=>'Bing',default=>mb_substr($h,0,80)}; }
    private static function screenBucket(int $w): string { return match(true){$w<=0=>'Tidak diketahui',$w<=480=>'≤ 480 px',$w<=768=>'481–768 px',$w<=1024=>'769–1024 px',$w<=1440=>'1025–1440 px',default=>'> 1440 px'}; }
    private static function isBot(): bool { return preg_match('/bot|crawler|spider|slurp|headless|lighthouse|preview/i',(string)($_SERVER['HTTP_USER_AGENT']??''))===1; }
    private static function retentionDays(): int { return max(30,min(365,(int)Config::get('ANALYTICS_RETENTION_DAYS','180'))); }
    private static function hasDimensions(): bool
    {
        return self::hasColumns('stats', [
            'page_path', 'content_type', 'content_slug', 'visitor_hash', 'session_hash',
            'device_type', 'os_family', 'browser_family', 'referrer_source', 'screen_bucket',
        ]);
    }
    private static function hasAdvancedSchema(): bool
    {
        try {
            $required = [
                'event_id', 'landing_path', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content',
                'cta_id', 'event_label', 'engagement_ms', 'scroll_depth', 'metric_name', 'metric_value',
            ];
            return self::hasColumns('stats', $required)
                && self::tableExists('analytics_sessions')
                && self::tableExists('analytics_visitors');
        } catch (Throwable) {
            return false;
        }
    }
    private static function hasColumn(string $table, string $column): bool
    {
        return self::hasColumns($table, [$column]);
    }
    private static function hasColumns(string $table, array $columns): bool
    {
        if ($columns === []) return false;
        try {
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $statement = Database::connection()->prepare(
                "SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema=DATABASE() AND table_name=? AND column_name IN ({$placeholders})"
            );
            $statement->execute(array_merge([$table], $columns));
            return (int) $statement->fetchColumn() === count($columns);
        } catch (Throwable) {
            return false;
        }
    }
    private static function tableExists(string $table): bool { try{$s=Database::connection()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:table');$s->execute(['table'=>$table]);return(int)$s->fetchColumn()>0;}catch(Throwable){return false;} }
}
