/**
 * Behaviour the theme's main.js used to provide, reimplemented without jQuery.
 * `initPageBehaviors()` is called after mount and after every navigation.
 */

/** Rebuild a native <select class="single-select"> as a nice-select widget. */
function enhanceSelect(select: HTMLSelectElement) {
	if (select.dataset.enhanced === '1') return;
	select.dataset.enhanced = '1';

	const wrapper = document.createElement('div');
	wrapper.className = 'nice-select single-select';
	// keep layout helpers such as `w-100` from the original select
	for (const cls of Array.from(select.classList)) {
		if (cls !== 'single-select') wrapper.classList.add(cls);
	}

	const current = document.createElement('span');
	current.className = 'current';
	current.textContent = select.options[select.selectedIndex]?.text ?? '';

	const list = document.createElement('ul');
	list.className = 'list';

	Array.from(select.options).forEach((option, index) => {
		const item = document.createElement('li');
		item.className = 'option' + (index === select.selectedIndex ? ' selected focus' : '');
		item.textContent = option.text;
		item.addEventListener('click', () => {
			select.selectedIndex = index;
			current.textContent = option.text;
			list.querySelectorAll('.option').forEach((el) => el.classList.remove('selected', 'focus'));
			item.classList.add('selected', 'focus');
			wrapper.classList.remove('open');
			select.dispatchEvent(new Event('change', { bubbles: true }));
		});
		list.appendChild(item);
	});

	wrapper.append(current, list);
	wrapper.addEventListener('click', () => wrapper.classList.toggle('open'));
	document.addEventListener('click', (event) => {
		if (!wrapper.contains(event.target as Node)) wrapper.classList.remove('open');
	});

	select.style.display = 'none';
	select.addEventListener('change', () => {
		const text = select.options[select.selectedIndex]?.text ?? '';
		if (current.textContent !== text) current.textContent = text;
	});
	select.parentNode?.insertBefore(wrapper, select);
}

function initSelects(root: ParentNode = document) {
	root.querySelectorAll<HTMLSelectElement>('select.single-select').forEach(enhanceSelect);
}

/** Quantity plus/minus buttons (`.quantity-plus` / `.quantity-minus` + `.qty-input`). */
function initQuantity() {
	if ((document.body as any).dataset.qtyBound === '1') return;
	(document.body as any).dataset.qtyBound = '1';

	document.addEventListener('click', (event) => {
		const button = (event.target as HTMLElement)?.closest('.quantity-plus, .quantity-minus');
		if (!button) return;
		event.preventDefault();

		const input = button.parentElement?.querySelector<HTMLInputElement>('.qty-input');
		if (!input) return;

		const value = parseInt(input.value, 10);
		if (Number.isNaN(value)) return;

		if (button.classList.contains('quantity-plus')) {
			input.value = String(value + 1);
		} else if (value > 1) {
			input.value = String(value - 1);
		}
		input.dispatchEvent(new Event('change', { bubbles: true }));
	});
}

export function initPageBehaviors() {
	if (typeof document === 'undefined') return;
	initSelects();
	initQuantity();
}
