<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'Monitoring-Standorte und IP-Bereiche',
        'description' => 'Alle Monitoring-Standorte und Quell-IP-Adressen von WebGuard, damit Sie Traffic allow-listen und Fehlalarme vermeiden können.',
        'keywords' => 'Monitoring Standorte, Monitoring IP-Adressen, Allow-List Monitoring, Uptime Monitoring IP-Bereiche',
        'og_title' => 'Monitoring-Standorte und IP-Bereiche',
        'og_description' => 'Transparente Liste der Monitoring-Standorte und Quell-IP-Adressen für Allow-Listing und stabile Uptime-Checks.',
    ],
    'hero' => [
        'eyebrow' => 'Monitoring-Transparenz',
        'title' => 'Monitoring-Standorte und Quell-IPs',
        'subtitle' => 'Nutzen Sie diese Liste, um Monitoring-Traffic in Firewalls, WAFs und Netzwerkrichtlinien freizugeben und Checks verlässlich zu halten.',
    ],
    'table' => [
        'caption' => 'Verfügbare Monitoring-Standorte',
        'location' => 'Standort',
        'ip_range' => 'IP-Adresse / Bereich',
        'ip_missing' => 'Noch nicht veröffentlicht',
        'empty' => 'Derzeit sind keine aktiven Monitoring-Standorte verfügbar.',
    ],
    'note' => [
        'title' => 'Warum das wichtig ist',
        'text' => 'Wenn Quell-IPs von Ihrer Infrastruktur blockiert werden, können Checks fehlschlagen, obwohl Ihr Service erreichbar ist. Das Allow-Listing dieser IPs reduziert False Negatives.',
    ],
    'guidance' => [
        'title' => 'Wichtig vor dem Aktivieren von Checks',
        'text' => 'Prüfen Sie, ob die unten aufgeführten IP-Adressen oder Bereiche in Ihrer Infrastruktur freigegeben sind. Werden sie blockiert, können Monitoring-Anfragen als Bot-Traffic eingestuft und abgewiesen werden.',
        'checklist_title' => 'Kurzcheck',
        'items' => [
            '1' => 'IP-Adressen in Firewall, WAF oder CDN-Allow-List eintragen.',
            '2' => 'Sicherstellen, dass Security-Regeln Monitoring-Traffic nicht als Bot blockieren.',
            '3' => 'Nach Änderungen einen Test-Check ausführen und Ergebnisse prüfen.',
        ],
    ],
    'footer_link' => 'Monitoring-Standorte',
];
