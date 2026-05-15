(function () {
  function normalizeSignatureValue(value) {
    if (value === null || value === undefined) {
      return '';
    }

    if (typeof value === 'object') {
      try {
        return JSON.stringify(value);
      } catch (_error) {
        return '';
      }
    }

    return String(value);
  }

  function buildCollectionSignature(items, fields) {
    if (!Array.isArray(items) || !Array.isArray(fields)) {
      return '';
    }

    return items.map((item) => fields.map((field) => {
      const value = typeof field === 'function' ? field(item) : item?.[field];
      return normalizeSignatureValue(value);
    }).join(':')).join('|');
  }

  window.UPTRealtime = {
    buildCollectionSignature,
  };
})();
