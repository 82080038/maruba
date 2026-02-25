(function($){
  const provinceSelect = $('#province');
  const citySelect = $('#city');
  const districtSelect = $('#district');
  const coopSelect = $('#cooperative_id');
  const configEl = $('#register-config');
  const coopsUrl = configEl.data('coops-url');
  let cooperatives = [];

  function resetSelect($sel, placeholder){
    $sel.html(`<option value="">${placeholder}</option>`).prop('disabled', true);
  }

  function populateSelect($sel, items, placeholder){
    resetSelect($sel, placeholder);
    items.forEach(val => $sel.append(`<option value="${val}">${val}</option>`));
    $sel.prop('disabled', false);
  }

  function filterCoops(){
    const prov = provinceSelect.val();
    const city = citySelect.val();
    const dist = districtSelect.val();
    resetSelect(coopSelect, 'Pilih koperasi');
    coopSelect.append('<option value="__new">Tidak ada di kecamatan saya - Daftarkan koperasi baru</option>');

    const filtered = cooperatives.filter(c =>
      (!prov || c.province === prov) &&
      (!city || c.city === city) &&
      (!dist || c.district === dist)
    );

    filtered.forEach(c => {
      coopSelect.append(`<option value="${c.id}">${c.name} (${c.district || 'Kecamatan?'})</option>`);
    });

    if (filtered.length === 0) {
      coopSelect.val('__new');
    }
  }

  function refreshLocations(){
    const provs = [...new Set(cooperatives.map(c => c.province).filter(Boolean))];
    populateSelect(provinceSelect, provs, 'Pilih Provinsi');
    resetSelect(citySelect, 'Pilih Kota/Kabupaten');
    resetSelect(districtSelect, 'Pilih Kecamatan');
    filterCoops();
  }

  provinceSelect.on('change', () => {
    const prov = provinceSelect.val();
    const cities = [...new Set(cooperatives.filter(c => !prov || c.province === prov).map(c => c.city).filter(Boolean))];
    populateSelect(citySelect, cities, 'Pilih Kota/Kabupaten');
    resetSelect(districtSelect, 'Pilih Kecamatan');
    filterCoops();
  });

  citySelect.on('change', () => {
    const prov = provinceSelect.val();
    const city = citySelect.val();
    const districts = [...new Set(cooperatives.filter(c => (!prov || c.province === prov) && (!city || c.city === city)).map(c => c.district).filter(Boolean))];
    populateSelect(districtSelect, districts, 'Pilih Kecamatan');
    filterCoops();
  });

  districtSelect.on('change', filterCoops);

  function loadCoops(){
    if (!coopsUrl) return;
    $.getJSON(coopsUrl)
      .done(resp => {
        if (resp && resp.success) {
          cooperatives = resp.data || [];
          refreshLocations();
        }
      })
      .fail(() => {
        console.error('Gagal memuat data koperasi');
      });
  }

  $(document).ready(loadCoops);
})(jQuery);
