document.addEventListener('DOMContentLoaded', function() {
    const codigoInput = document.getElementById('codigo');

    codigoInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    codigoInput.addEventListener('input', function(e) {
        if (this.value.length === 5) {
        }
    });

    codigoInput.addEventListener('focus', function() {
        this.select();
    });
});