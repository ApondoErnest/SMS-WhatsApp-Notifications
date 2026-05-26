<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ strtoupper($template->language) }}</span>
            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $template->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                {{ $template->status === 'active' ? __('Active') : __('Inactive') }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('templates.edit', $template) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>
            <form method="POST" action="{{ route('templates.destroy', $template) }}" onsubmit="return confirm('{{ __('Delete this template?') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
            </form>
        </div>
    </div>
    <h3 class="font-semibold text-slate-800">{{ $template->title }}</h3>
    <p class="mt-2 whitespace-pre-wrap text-sm text-slate-600">{{ $template->content }}</p>
    <p class="mt-3 text-xs text-slate-400">
        {{ __('Variables:') }} <code class="text-indigo-600">{customer_name}</code>, <code class="text-indigo-600">{licence_plate}</code>, <code class="text-indigo-600">{expiration_date}</code>
    </p>
</div>
