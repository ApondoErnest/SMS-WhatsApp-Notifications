document.addEventListener('alpine:init', () => {
    Alpine.data('scheduleDateRange', (config) => ({
        from: config.from,
        to: config.to,
        locale: config.locale || 'en',
        openFrom: false,
        openTo: false,
        viewFrom: { month: 0, year: 0 },
        viewTo: { month: 0, year: 0 },

        init() {
            this.resetViews();
        },

        resetViews() {
            const base = this.parseIso(this.from) ?? new Date();
            this.viewFrom = { month: base.getMonth(), year: base.getFullYear() };
            const baseTo = this.parseIso(this.to) ?? base;
            this.viewTo = { month: baseTo.getMonth(), year: baseTo.getFullYear() };
        },

        monthNames() {
            return this.locale === 'fr'
                ? ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
                : ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        },

        weekdayLabels() {
            return this.locale === 'fr'
                ? ['Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa', 'Di']
                : ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];
        },

        years() {
            const current = new Date().getFullYear();
            const list = [];
            for (let y = current - 15; y <= current + 5; y++) {
                list.push(y);
            }
            return list;
        },

        view(target) {
            return target === 'from' ? this.viewFrom : this.viewTo;
        },

        parseIso(value) {
            if (!value) {
                return null;
            }
            const [y, m, d] = String(value).split('-').map(Number);
            if (!y || !m || !d) {
                return null;
            }
            return new Date(y, m - 1, d);
        },

        toIso(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        },

        formatDisplay(iso) {
            const date = this.parseIso(iso);
            if (!date) {
                return '';
            }
            const d = String(date.getDate()).padStart(2, '0');
            const m = String(date.getMonth() + 1).padStart(2, '0');
            return `${d}/${m}/${date.getFullYear()}`;
        },

        daysFor(view) {
            const first = new Date(view.year, view.month, 1);
            const startOffset = (first.getDay() + 6) % 7;
            const daysInMonth = new Date(view.year, view.month + 1, 0).getDate();
            const cells = [];

            for (let i = 0; i < startOffset; i++) {
                cells.push({ empty: true, key: `pad-${view.year}-${view.month}-${i}` });
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(view.year, view.month, day);
                const iso = this.toIso(date);
                cells.push({
                    empty: false,
                    key: `${view.year}-${view.month}-${day}`,
                    day,
                    iso,
                    isToday: this.isSameDay(date, new Date()),
                    inRange: this.isInRange(date),
                    isStart: this.from === iso,
                    isEnd: this.to === iso,
                });
            }

            return cells;
        },

        isSameDay(a, b) {
            return a.getFullYear() === b.getFullYear()
                && a.getMonth() === b.getMonth()
                && a.getDate() === b.getDate();
        },

        isInRange(date) {
            const from = this.parseIso(this.from);
            const to = this.parseIso(this.to);
            if (!from || !to) {
                return false;
            }
            const t = date.getTime();
            return t >= from.getTime() && t <= to.getTime();
        },

        dayClasses(cell) {
            if (cell.isStart || cell.isEnd) {
                return 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-700';
            }
            if (cell.inRange) {
                return 'bg-indigo-50 text-indigo-900 hover:bg-indigo-100';
            }
            if (cell.isToday) {
                return 'font-semibold text-indigo-700 ring-1 ring-indigo-400 ring-inset hover:bg-indigo-50';
            }
            return 'text-slate-700 hover:bg-slate-100';
        },

        selectFrom(iso) {
            this.from = iso;
            if (this.to && iso > this.to) {
                this.to = iso;
            }
            this.openFrom = false;
        },

        selectTo(iso) {
            this.to = iso;
            if (this.from && iso < this.from) {
                this.from = iso;
            }
            this.openTo = false;
        },

        goToToday(target) {
            const today = new Date();
            const view = this.view(target);
            view.month = today.getMonth();
            view.year = today.getFullYear();
        },

        selectToday(target) {
            const iso = this.toIso(new Date());
            if (target === 'from') {
                this.selectFrom(iso);
            } else {
                this.selectTo(iso);
            }
        },

        prevMonth(target) {
            const view = this.view(target);
            if (view.month === 0) {
                view.month = 11;
                view.year -= 1;
            } else {
                view.month -= 1;
            }
        },

        nextMonth(target) {
            const view = this.view(target);
            if (view.month === 11) {
                view.month = 0;
                view.year += 1;
            } else {
                view.month += 1;
            }
        },

        clearRange() {
            this.from = null;
            this.to = null;
            this.resetViews();
        },

        closeAll() {
            this.openFrom = false;
            this.openTo = false;
        },

        toggleFrom() {
            this.openFrom = !this.openFrom;
            this.openTo = false;
            if (this.openFrom) {
                this.resetViews();
            }
        },

        toggleTo() {
            this.openTo = !this.openTo;
            this.openFrom = false;
            if (this.openTo) {
                this.resetViews();
            }
        },
    }));
});
