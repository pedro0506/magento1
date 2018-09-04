function setLocationAndLoading(location) {
    disableElements('scalable');
    Element.show('loading-mask');
    setLocation(location);
}

function confirmSetLocationAndLoading(message, location) {
    if (confirm(message)) {
        setLocationAndLoading(location);
    }
    return false;
}