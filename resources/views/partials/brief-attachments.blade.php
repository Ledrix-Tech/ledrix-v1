@php

    use App\Support\BriefServiceCatalog;



    $attachments = $attachments ?? [];

@endphp



@if (! empty($attachments))

    <div class="mt-3 text-start">

        <strong>Uploaded files</strong>

        <ul id="existing-files-list" class="mb-0 mt-2">

            @foreach ($attachments as $file)

                <li>

                    <a href="{{ BriefServiceCatalog::attachmentUrl($file) }}" target="_blank" rel="noopener">

                        {{ basename($file) }}

                    </a>

                    @if (($mode ?? 'dashboard') !== 'view')

                        <label class="text-danger ms-2" style="cursor:pointer;">

                            <input type="checkbox" name="remove_attachments[]" value="{{ $file }}">

                            Remove

                        </label>

                    @endif

                </li>

            @endforeach

        </ul>

    </div>

@endif

