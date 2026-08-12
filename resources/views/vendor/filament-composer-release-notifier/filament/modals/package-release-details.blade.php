@php
    /** @var \MominAlZaraa\FilamentComposerReleaseNotifier\Models\ComposerReleasePackageSnapshot $record */
@endphp
<div class="space-y-4 text-sm fi-text-color-700">
    <div class="rounded-lg fi-bg-color-100 p-4">
        <p class="font-medium fi-text-color-950">{{ $record->package_name }}</p>
        <p class="mt-1 fi-text-color-600">
            {{ __('Repository') }}:
            <span class="font-mono text-xs fi-text-color-700">{{ $record->repository_owner }}/{{ $record->repository_name }}</span>
        </p>
        <dl class="mt-3 grid gap-2 sm:grid-cols-2">
            <div>
                <dt class="fi-text-color-500">{{ __('Installed') }}</dt>
                <dd class="font-mono fi-text-color-950">{{ $record->installed_version }}</dd>
            </div>
            <div>
                <dt class="fi-text-color-500">{{ __('Latest release') }}</dt>
                <dd class="font-mono fi-text-color-950">{{ $record->latest_release_tag ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    @if($record->last_error)
        <p class="fi-color-warning">{{ $record->last_error }}</p>
    @endif

    @if($record->release_notes)
        <div>
            <h4 class="mb-1 font-semibold fi-text-color-950">{{ __('Release notes') }}</h4>
            <pre class="max-h-48 overflow-y-auto whitespace-pre-wrap rounded-md fi-bg-color-100 p-3 text-xs fi-text-color-800">{{ $record->release_notes }}</pre>
        </div>
    @endif

    @if($record->commits_payload && count($record->commits_payload))
        <div>
            <h4 class="mb-2 font-semibold fi-text-color-950">{{ __('Commits (summary)') }}</h4>
            <ul class="max-h-56 space-y-2 overflow-y-auto">
                @foreach($record->commits_payload as $c)
                    <li class="border-b border-black/10 pb-2">
                        <span class="font-mono text-xs fi-text-color-500">{{ $c['sha'] ?? '' }}</span>
                        @if(!empty($c['html_url']))
                            <a href="{{ $c['html_url'] }}" target="_blank" rel="noopener" class="fi-color-primary text-xs underline">{{ __('view') }}</a>
                        @endif
                        <p class="fi-text-color-950">{{ $c['message'] ?? '' }}</p>
                        @if(!empty($c['date']))
                            <p class="text-xs fi-text-color-500">{{ $c['date'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($record->compare_html_url)
        <a href="{{ $record->compare_html_url }}" target="_blank" rel="noopener noreferrer" class="fi-color-primary inline-flex items-center gap-1 underline">
            @if(str_contains($record->compare_html_url, 'packagist.org/packages/'))
                {{ __('View package on Packagist') }}
            @else
                {{ __('Open full compare on GitHub') }}
            @endif
        </a>
    @endif

    <p class="text-xs fi-text-color-500">
        @if($record->commits_payload && count($record->commits_payload))
            {{ __('Large comparisons may be truncated on GitHub’s website; commits above are a short summary from the GitHub API.') }}
        @else
            {{ __('When commit summaries are not fetched from the GitHub API, use the link above for the full browser compare (no token required for the public GitHub website).') }}
        @endif
    </p>
</div>
