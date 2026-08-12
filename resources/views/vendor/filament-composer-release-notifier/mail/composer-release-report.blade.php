<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Composer release report</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; padding: 24px; }
        .wrap { max-width: 640px; margin: 0 auto; background: #1e293b; border-radius: 12px; padding: 24px; border: 1px solid #334155; }
        h1 { font-size: 20px; margin: 0 0 8px; color: #f8fafc; }
        p.lead { margin: 0 0 20px; color: #94a3b8; font-size: 14px; line-height: 1.5; }
        .stats { display: table; width: 100%; margin-bottom: 24px; border-collapse: collapse; }
        .stats td { padding: 12px 16px; border: 1px solid #334155; font-size: 14px; }
        .stats td.label { color: #94a3b8; width: 55%; }
        .stats td.value { font-weight: 600; color: #38bdf8; text-align: right; }
        h2 { font-size: 15px; margin: 0 0 12px; color: #f1f5f9; }
        table.pkg { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.pkg th, table.pkg td { padding: 10px 12px; border: 1px solid #334155; text-align: left; }
        table.pkg th { background: #0f172a; color: #94a3b8; font-weight: 600; }
        table.pkg td a { color: #38bdf8; text-decoration: none; }
        .footer { margin-top: 24px; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Composer release notifier</h1>
    <p class="lead">Informational summary of Composer packages referenced in <code>composer.json</code> that appear to be behind the latest GitHub release.</p>

    <table class="stats" role="presentation">
        <tr><td class="label">Packages tracked (GitHub)</td><td class="value">{{ $tracked }}</td></tr>
        <tr><td class="label">Behind latest release</td><td class="value">{{ $outdated }}</td></tr>
        <tr><td class="label">Skipped (non-GitHub / excluded)</td><td class="value">{{ $skipped }}</td></tr>
    </table>

    @if(count($outdatedPackages))
        <h2>Outdated packages</h2>
        <table class="pkg">
            <thead>
            <tr>
                <th>Package</th>
                <th>Installed</th>
                <th>Latest</th>
                <th>Compare</th>
            </tr>
            </thead>
            <tbody>
            @foreach($outdatedPackages as $row)
                <tr>
                    <td>{{ $row['package_name'] }}</td>
                    <td>{{ $row['installed_version'] }}</td>
                    <td>{{ $row['latest_release_tag'] }}</td>
                    <td>
                        @if(!empty($row['compare_html_url']))
                            <a href="{{ $row['compare_html_url'] }}">GitHub</a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p class="lead" style="margin-bottom:0;">No outdated packages detected in this run.</p>
    @endif

    <p class="footer">This email was sent by the <strong>Filament Composer Release Notifier</strong> package. It does not install updates automatically.</p>
</div>
</body>
</html>
