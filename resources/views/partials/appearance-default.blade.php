{{-- Light mode is the shop's default look. Visitors who pick dark (moon button) keep their choice. --}}
<script>
    try {
        if (!localStorage.getItem('flux.appearance') || localStorage.getItem('flux.appearance') === 'system') {
            localStorage.setItem('flux.appearance', 'light');
        }
    } catch (e) {}
</script>
