<script lang="ts">
	import { onMount } from 'svelte';

	onMount(() => {
		const el = document.getElementById('exampleModal');
		if (!el) return;

		let modal: { show: () => void; dispose: () => void } | null = null;
		let cancelled = false;

		const timer = window.setTimeout(async () => {
			// Lazy: Bootstrap's modal touches `document` on import.
			const { default: Modal } = await import('bootstrap/js/dist/modal');
			if (cancelled) return;
			const instance = new Modal(el);
			modal = instance;
			instance.show();
		}, 500);

		return () => {
			cancelled = true;
			window.clearTimeout(timer);
			modal?.dispose();
		};
	});
</script>

<!-- Newsletter Modal Area -->
<div class="modal fade bd-example-modal-lg common-newsletter-modal" id="exampleModal" tabindex="-1" role="dialog"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body modal1 modal-bg">
                <div class="row">
                    <div class="col-12">
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>

                    </div>
                    <div class="col-lg-12">
                        <div class="row align-items-center">
                            <div class="col-lg-5 col-md-12">
                                <div class="offer-modal-img d-none d-lg-block">
                                    <img src="/assets/images/modal/common-modal.jpg" alt="img">
                                </div>
                            </div>
                            <div class="col-lg-7 col-md-12">
                                <div class="offer-modal-right">
                                    <h3>Subcribe to Our Newsletter</h3>
                                    <p>Subscribe to our newsletter and Save your <span>20% money</span> with
                                        discount code today.</p>
                                    <form action="#!">
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control" placeholder="Enter your email">
                                            <div class="input-group-append">
                                                <button class="theme-btn style6">Subscribe</button>
                                            </div>
                                        </div>
                                        <div class="check_boxed_modal">
                                            <input type="checkbox" id="vehicle1" name="vehicle1" value="Bike">
                                            <label for="vehicle1">Do not show this window</label>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
