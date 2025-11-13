(() => {
    const script = document.currentScript;


    if(!script){
        return;
    }
    
    const widgetUrl = script.dataset.widgetUrl;
    
    const iframe = document.createElement('iframe');
    iframe.src = `${widgetUrl}?frameId=${Date.now()}`;
    iframe.setAttribute('data-auto-resize', '');
    iframe.setAttribute('scrolling', 'no');
    iframe.style.cssText = 'border:none;width:100%;display:block';


    
    script.parentNode?.insertBefore(iframe, script);
    
    window.addEventListener('message', (event) => {
        if (event.data?.type === 'resize') {
            iframe.style.height = `${event.data.height}px`;
        }
    });
})();