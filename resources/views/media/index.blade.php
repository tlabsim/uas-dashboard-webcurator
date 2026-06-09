@extends('web_curator::layouts.default')

@php
    $folderIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2"/></svg>

SVG;
    $folderOpenIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M10 13a2 2 0 1 0 4 0a2 2 0 1 0-4 0m2 2v4"/><path d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2"/></g></svg>
SVG;
    $imageIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M15 8h.01M3 6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3z"/><path d="m3 16l5-5c.928-.893 2.072-.893 3 0l5 5"/><path d="m14 14l1-1c.928-.893 2.072-.893 3 0l3 3"/></g></svg>
SVG;
    $videoIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m15 10l4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14zM3 8a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
SVG;
    $documentIcon = <<<'SVG'
<svg viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M14.5 19h-2c-2.829 0-4.243 0-5.121-.879c-.88-.878-.88-2.293-.88-5.121V8c0-2.828 0-4.243.88-5.121C8.256 2 9.67 2 12.499 2h1.344c.818 0 1.226 0 1.594.152c.367.152.656.442 1.234 1.02l2.657 2.656c.578.578.867.868 1.02 1.235c.152.368.152.776.152 1.594V13c0 2.828 0 4.243-.879 5.121C18.743 19 17.328 19 14.5 19"/><path d="M15 2.5v1c0 1.886 0 2.828.586 3.414c.585.586 1.528.586 3.414.586h1M6.5 5a3 3 0 0 0-3 3v8c0 2.828 0 4.243.878 5.121C5.257 22 6.671 22 9.5 22h5a3 3 0 0 0 3-3M10 11h4m-4 4h7"/></g></svg>
SVG;
    $moreIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="currentColor"><circle cx="12" cy="6" r="1.75"/><circle cx="12" cy="12" r="1.75"/><circle cx="12" cy="18" r="1.75"/></g></svg>
SVG;
    $renameIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="currentColor" d="M14.5 2a.48.48 0 0 1 .352.148A.48.48 0 0 1 15 2.5a.48.48 0 0 1-.148.352A.48.48 0 0 1 14.5 3H13v14h1.5a.48.48 0 0 1 .352.148a.48.48 0 0 1 .148.352a.48.48 0 0 1-.148.352a.48.48 0 0 1-.352.148h-4a.48.48 0 0 1-.352-.148A.48.48 0 0 1 10 17.5a.48.48 0 0 1 .148-.352A.48.48 0 0 1 10.5 17H12V3h-1.5a.48.48 0 0 1-.352-.148A.48.48 0 0 1 10 2.5a.48.48 0 0 1 .148-.352A.48.48 0 0 1 10.5 2zM11 5H5q-.414 0-.773.156a2.1 2.1 0 0 0-.641.43a1.9 1.9 0 0 0-.43.633Q3.008 6.579 3 7v6q0 .414.156.773q.157.36.43.641t.633.43T5 15h6v1H5q-.625 0-1.164-.234a3.1 3.1 0 0 1-.953-.641A2.95 2.95 0 0 1 2 13V7q0-.617.234-1.164a3 3 0 0 1 .641-.953q.406-.406.953-.649Q4.375 3.992 5 4h6zm4-1q.618 0 1.164.234a3 3 0 0 1 1.602 1.602Q18.008 6.39 18 7v6q0 .625-.234 1.164q-.235.54-.641.953q-.406.414-.96.649A3 3 0 0 1 15 16h-1v-1h1q.414 0 .773-.156a2.1 2.1 0 0 0 .641-.43a1.9 1.9 0 0 0 .43-.633q.148-.36.156-.781V7a1.9 1.9 0 0 0-.156-.773a2.1 2.1 0 0 0-.43-.641a1.9 1.9 0 0 0-.633-.43Q15.421 5.008 15 5h-1V4zM7.5 6a.5.5 0 0 1 .454.292l2.75 6a.5.5 0 1 1-.908.416l-.784-1.71L9 11H6l-.013-.002l-.783 1.71a.5.5 0 1 1-.908-.416l2.75-6l.034-.063A.5.5 0 0 1 7.5 6m-1.055 4h2.11L7.5 7.698z"/></svg>
SVG;
    $propertiesIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="currentColor" d="M16 13a1 1 0 0 1 1 1v9a1 1 0 1 1-2 0v-9a1 1 0 0 1 1-1m0-2a1.5 1.5 0 1 0 0-3a1.5 1.5 0 0 0 0 3M2 16C2 8.268 8.268 2 16 2s14 6.268 14 14s-6.268 14-14 14S2 23.732 2 16M16 4C9.373 4 4 9.373 4 16s5.373 12 12 12s12-5.373 12-12S22.627 4 16 4"/></svg>
SVG;
    $deleteIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M10.31 2.25h3.38c.217 0 .406 0 .584.028a2.25 2.25 0 0 1 1.64 1.183c.084.16.143.339.212.544l.111.335l.03.085a1.25 1.25 0 0 0 1.233.825h3a.75.75 0 0 1 0 1.5h-17a.75.75 0 0 1 0-1.5h3.09a1.25 1.25 0 0 0 1.173-.91l.112-.335c.068-.205.127-.384.21-.544a2.25 2.25 0 0 1 1.641-1.183c.178-.028.367-.028.583-.028m-1.302 3a3 3 0 0 0 .175-.428l.1-.3c.091-.273.112-.328.133-.368a.75.75 0 0 1 .547-.395a3 3 0 0 1 .392-.009h3.29c.288 0 .348.002.392.01a.75.75 0 0 1 .547.394c.021.04.042.095.133.369l.1.3l.039.112q.059.164.136.315z" clip-rule="evenodd"/><path fill="currentColor" d="M5.915 8.45a.75.75 0 1 0-1.497.1l.464 6.952c.085 1.282.154 2.318.316 3.132c.169.845.455 1.551 1.047 2.104s1.315.793 2.17.904c.822.108 1.86.108 3.146.108h.879c1.285 0 2.324 0 3.146-.108c.854-.111 1.578-.35 2.17-.904c.591-.553.877-1.26 1.046-2.104c.162-.813.23-1.85.316-3.132l.464-6.952a.75.75 0 0 0-1.497-.1l-.46 6.9c-.09 1.347-.154 2.285-.294 2.99c-.137.685-.327 1.047-.6 1.303c-.274.256-.648.422-1.34.512c-.713.093-1.653.095-3.004.095h-.774c-1.35 0-2.29-.002-3.004-.095c-.692-.09-1.066-.256-1.34-.512c-.273-.256-.463-.618-.6-1.302c-.14-.706-.204-1.644-.294-2.992z"/><path fill="currentColor" d="M9.425 10.254a.75.75 0 0 1 .821.671l.5 5a.75.75 0 0 1-1.492.15l-.5-5a.75.75 0 0 1 .671-.821m5.15 0a.75.75 0 0 1 .671.82l-.5 5a.75.75 0 0 1-1.492-.149l.5-5a.75.75 0 0 1 .82-.671"/></svg>
SVG;
    $galleryIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36"><path fill="currentColor" d="M32.12 10H3.88A1.88 1.88 0 0 0 2 11.88v18.24A1.88 1.88 0 0 0 3.88 32h28.24A1.88 1.88 0 0 0 34 30.12V11.88A1.88 1.88 0 0 0 32.12 10M32 30H4V12h28Z" class="clr-i-outline clr-i-outline-path-1"/><path fill="currentColor" d="M8.56 19.45a3 3 0 1 0-3-3a3 3 0 0 0 3 3m0-4.6A1.6 1.6 0 1 1 7 16.45a1.6 1.6 0 0 1 1.56-1.6" class="clr-i-outline clr-i-outline-path-2"/><path fill="currentColor" d="m7.9 28l6-6l3.18 3.18L14.26 28h2l7.46-7.46L30 26.77v-2L24.2 19a.71.71 0 0 0-1 0l-5.16 5.16l-3.67-3.66a.71.71 0 0 0-1 0L5.92 28Z" class="clr-i-outline clr-i-outline-path-3"/><path fill="currentColor" d="M30.14 3a1 1 0 0 0-1-1h-22a1 1 0 0 0-1 1v1h24Z" class="clr-i-outline clr-i-outline-path-4"/><path fill="currentColor" d="M32.12 7a1 1 0 0 0-1-1h-26a1 1 0 0 0-1 1v1h28Z" class="clr-i-outline clr-i-outline-path-5"/><path fill="none" d="M0 0h36v36H0z"/></svg>
SVG;
    $galleryItemIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M15 6h.01M3 6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v8a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3z"/><path d="m3 13l4-4a3 5 0 0 1 3 0l4 4"/><path d="m13 12l2-2a3 5 0 0 1 3 0l3 3M8 21h.01M12 21h.01M16 21h.01"/></g></svg>
SVG;
    $galleryAddIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4l3 3h7a2 2 0 0 1 2 2v3.5M16 19h6m-3-3v6"/></svg>
SVG;
    $featureIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M11.292 3.308c-.394.514-.838 1.308-1.484 2.466l-.327.587l-.059.106c-.3.54-.555.998-.964 1.308c-.413.314-.917.427-1.503.559l-.114.026l-.636.144c-1.255.284-2.11.479-2.694.71c-.571.224-.691.409-.737.556c-.049.156-.05.395.29.937c.347.55.932 1.236 1.786 2.236l.434.507l.075.087c.403.47.739.862.893 1.358c.153.493.102 1.01.04 1.638l-.01.117l-.066.677c-.13 1.332-.216 2.25-.187 2.91c.03.66.169.842.28.926c.098.075.28.157.873-.013c.603-.172 1.405-.539 2.58-1.08l.596-.274l.109-.05c.545-.253 1.017-.471 1.533-.471s.988.218 1.533.47q.053.026.11.05l.595.275c1.175.541 1.977.908 2.58 1.08c.593.17.775.088.873.013c.111-.084.25-.267.28-.926c.03-.66-.058-1.578-.187-2.91l-.066-.677l-.01-.117c-.062-.628-.113-1.145.04-1.638c.154-.496.49-.888.893-1.358l.075-.087l.434-.507c.854-1 1.439-1.686 1.785-2.236c.341-.542.34-.78.291-.937c-.046-.147-.166-.332-.737-.556c-.585-.231-1.439-.426-2.694-.71l-.636-.144l-.114-.026c-.586-.132-1.09-.245-1.503-.559c-.41-.31-.663-.767-.964-1.308l-.058-.106l-.328-.587c-.646-1.158-1.09-1.952-1.484-2.466S12.114 2.75 12 2.75s-.315.044-.708.558m-1.19-.912C10.577 1.774 11.166 1.25 12 1.25s1.422.524 1.899 1.146c.468.612.965 1.503 1.572 2.592l.359.643c.392.704.493.854.619.95c.12.091.277.143 1.04.316l.7.158c1.176.266 2.145.485 2.85.763c.732.289 1.373.714 1.62 1.507c.244.785-.03 1.507-.454 2.18c-.412.655-1.07 1.425-1.874 2.365l-.475.555c-.517.604-.625.752-.676.915c-.051.167-.047.36.032 1.165l.071.738c.122 1.256.221 2.28.186 3.06c-.035.795-.215 1.557-.87 2.055c-.668.506-1.445.45-2.195.234c-.727-.208-1.633-.625-2.733-1.132l-.656-.302c-.718-.33-.871-.383-1.015-.383s-.297.053-1.015.383l-.655.302c-1.101.507-2.007.924-2.734 1.132c-.75.215-1.527.272-2.194-.234c-.656-.498-.836-1.26-.871-2.054c-.035-.78.064-1.805.186-3.06l.072-.739c.078-.806.082-.998.03-1.165c-.05-.163-.158-.31-.675-.915l-.475-.555c-.803-.94-1.461-1.71-1.873-2.364c-.425-.674-.699-1.396-.455-2.181c.247-.793.888-1.218 1.62-1.507c.705-.278 1.674-.497 2.85-.763l.063-.014l.636-.144c.764-.173.92-.225 1.041-.317c.126-.095.227-.245.62-.949l.358-.643c.607-1.09 1.104-1.98 1.572-2.592" clip-rule="evenodd"/></svg>
SVG;
    $publishIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m14 10l-3 3m9.288-9.969a.535.535 0 0 1 .68.681l-5.924 16.93a.535.535 0 0 1-.994.04l-3.219-7.242a.54.54 0 0 0-.271-.271l-7.242-3.22a.535.535 0 0 1 .04-.993z"/></svg>
SVG;
    $unpublishIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m10 14l2-2m2-2l7-7M10.718 6.713L21 3l-3.715 10.289m-1.063 2.941L14.5 21a.55.55 0 0 1-1 0L10 14l-7-3.5a.55.55 0 0 1 0-1l4.772-1.723M3 3l18 18"/></svg>
SVG;
    $chevronRightIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m7 5l6 5l-6 5"/></svg>
SVG;
    $removeIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 19H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4l3 3h7a2 2 0 0 1 2 2v4m1 9l-5-5m0 5l5-5"/></svg>
SVG;
    $moveToFolderIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M3 21v-4a3 3 0 0 1 3-3h5"/><path d="m8 17l3-3l-3-3"/><path d="M3 11V6a2 2 0 0 1 2-2h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-8"/></g></svg>
SVG;
    $downloadIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M12.554 16.506a.75.75 0 0 1-1.107 0l-4-4.375a.75.75 0 0 1 1.107-1.012l2.696 2.95V3a.75.75 0 0 1 1.5 0v11.068l2.697-2.95a.75.75 0 1 1 1.107 1.013z"/><path fill="currentColor" d="M3.75 15a.75.75 0 0 0-1.5 0v.055c0 1.367 0 2.47.117 3.337c.12.9.38 1.658.981 2.26c.602.602 1.36.86 2.26.982c.867.116 1.97.116 3.337.116h6.11c1.367 0 2.47 0 3.337-.116c.9-.122 1.658-.38 2.26-.982s.86-1.36.982-2.26c.116-.867.116-1.97.116-3.337V15a.75.75 0 0 0-1.5 0c0 1.435-.002 2.436-.103 3.192c-.099.734-.28 1.122-.556 1.399c-.277.277-.665.457-1.4.556c-.755.101-1.756.103-3.191.103H9c-1.435 0-2.437-.002-3.192-.103c-.734-.099-1.122-.28-1.399-.556c-.277-.277-.457-.665-.556-1.4c-.101-.755-.103-1.756-.103-3.191"/></svg>
SVG;
    $sizeSmallIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="currentColor" d="M3 3H1v2h2zm0 4H1v2h2zm-2 4h2v2H1zm6-8H5v2h2zM5 7h2v2H5zm2 4H5v2h2zm2-8h2v2H9zm6 0h-2v2h2zM9 7h2v2H9zm6 0h-2v2h2zm-6 4h2v2H9zm6 0h-2v2h2z"/></svg>
SVG;
    $sizeMediumIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="currentColor" d="M1 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1zM1 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1zM1 12a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1z"/></svg>
SVG;
    $sizeLargeIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="currentColor" d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5zm8 0A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5zm-8 8A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5zm8 0A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5z"/></svg>
SVG;
    $goUpIcon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="m4 10l-.707.707L2.586 10l.707-.707zm17 8a1 1 0 1 1-2 0zM8.293 15.707l-5-5l1.414-1.414l5 5zm-5-6.414l5-5l1.414 1.414l-5 5zM4 9h10v2H4zm17 7v2h-2v-2zm-7-7a7 7 0 0 1 7 7h-2a5 5 0 0 0-5-5z"/></svg>
SVG;
    $emptyStateIcon = asset('vendor/webcurator/no_media.png');
@endphp

@section('dashboard-content')
<div class="flex flex-col w-full h-full"
     x-data="mediaWorkspace({
        routes: {
            refresh: @js(route('dashboard.web_curator.media.index')),
            upload: @js(route('dashboard.web_curator.media.upload')),
            folderStore: @js(route('dashboard.web_curator.media.folders.store')),
            folderUpdate: @js(route('dashboard.web_curator.media.folders.update', ['id' => '__ID__'])),
            folderDelete: @js(route('dashboard.web_curator.media.folders.destroy', ['id' => '__ID__'])),
            folderReorder: @js(route('dashboard.web_curator.media.folders.reorder')),
            mediaUpdate: @js(route('dashboard.web_curator.media.items.update', ['id' => '__ID__'])),
            mediaDownload: @js(route('dashboard.web_curator.media.items.download', ['id' => '__ID__'])),
            mediaMove: @js(route('dashboard.web_curator.media.items.move', ['id' => '__ID__'])),
            mediaDelete: @js(route('dashboard.web_curator.media.items.destroy', ['id' => '__ID__'])),
            galleryStore: @js(route('dashboard.web_curator.media.galleries.store')),
            galleryUpdate: @js(route('dashboard.web_curator.media.galleries.update', ['id' => '__ID__'])),
            galleryDelete: @js(route('dashboard.web_curator.media.galleries.destroy', ['id' => '__ID__'])),
            galleryAddItems: @js(route('dashboard.web_curator.media.galleries.add-items', ['id' => '__ID__'])),
            galleryRemoveItems: @js(route('dashboard.web_curator.media.galleries.remove-items', ['id' => '__ID__'])),
        },
        payload: @js([
            'context' => $context,
            'folderTree' => $folderTree,
            'foldersFlat' => $foldersFlat,
            'mediaItems' => $mediaItems,
            'libraryMediaItems' => $libraryMediaItems,
            'galleries' => $galleries,
            'activeGallery' => $activeGallery,
            'currentFolder' => $currentFolder,
            'typeStats' => $typeStats,
            'galleryCount' => $galleryCount,
            'filters' => $filters,
        ]),
    })"
     x-init="init()">
    <!-- <div class="page-header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="page-title leading-none">Media Library</h2>
            <p class="mt-1 text-sm text-gray-600">
                <span class="font-semibold text-[var(--accent)]">{{ $context['entity_name'] }}</span>
            </p>
        </div>
    </div> -->

    <div class="wc-media-workspace-shell card grow !p-0 !rounded-none !border-0   m-[-1rem] lg:m-[-2rem]">
        <div class="wc-media-layout h-full">
            <aside class="wc-media-sidebar">
                <div class="wc-media-sidebar-tabs">
                    <button type="button" class="wc-media-tab-button" :class="{ 'is-active': tab === 'folders' }" @click="switchTab('folders')">
                        <span class="h-5 w-5">{!! $folderIcon !!}</span>
                        <span>Folders</span>
                    </button>
                    <button type="button" class="wc-media-tab-button" :class="{ 'is-active': tab === 'galleries' }" @click="switchTab('galleries')">
                        <span class="h-5 w-5">{!! $galleryIcon !!}</span>
                        <span>Galleries</span>
                    </button>
                </div>

                <div class="wc-media-sidebar-toolbar">
                    <div class="flex items-center gap-2">
                        <button type="button" class="wc-media-mini-button" @click="tab === 'folders' ? startCreateFolder() : startCreateGallery()">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Add</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-1" x-show="tab === 'folders'" x-cloak>
                        <button type="button" class="wc-media-icon-toggle" :class="{ 'is-active': folderSort === 'name' }" @click="setFolderSort('name')" title="Sort by name">A</button>
                        <button type="button" class="wc-media-icon-toggle" :class="{ 'is-active': folderSort === 'newest' }" @click="setFolderSort('newest')" title="Sort by newest">
                            <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h10M4 18h4"/></svg>
                        </button>
                        <button type="button" class="wc-media-icon-toggle" :class="{ 'is-active': folderSort === 'updated' }" @click="setFolderSort('updated')" title="Sort by updated">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-1" x-show="tab === 'galleries'" x-cloak>
                        <button type="button" class="wc-media-icon-toggle" :class="{ 'is-active': gallerySort === 'name' }" @click="setGallerySort('name')" title="Sort by name">A</button>
                        <button type="button" class="wc-media-icon-toggle" :class="{ 'is-active': gallerySort === 'newest' }" @click="setGallerySort('newest')" title="Sort by newest">
                            <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h10M4 18h4"/></svg>
                        </button>
                        <button type="button" class="wc-media-icon-toggle" :class="{ 'is-active': gallerySort === 'updated' }" @click="setGallerySort('updated')" title="Sort by updated">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </button>
                    </div>
                </div>

                <div class="wc-media-sidebar-list custom-scrollbar" @dragover.prevent>
                    <div x-show="tab === 'folders'">
                        <div class="wc-media-collection-row is-root"
                             :class="{ 'is-active': tab === 'folders' && !currentFolderId, 'is-drop-target': dragOverRoot || mediaDragOverRoot }"
                             draggable="false"
                             @click="selectFolder(null)"
                             @dragover.prevent="handleFolderTargetDragOver()"
                             @drop.prevent="handleFolderTargetDrop()">
                            <div class="wc-media-collection-copy">
                                <button type="button" class="wc-tree-chevron" x-show="foldersFlat.length > 0" x-cloak @click.stop="rootExpanded = !rootExpanded" :class="{ 'is-expanded': rootExpanded }">
                                    <span class="h-3.5 w-3.5">{!! $chevronRightIcon !!}</span>
                                </button>
                                <span class="wc-media-collection-icon">{!! $folderOpenIcon !!}</span>
                                <span class="wc-media-collection-title">Root Folder</span>
                            </div>
                            <span class="wc-media-collection-count-badge" x-text="rootDisplayCount"></span>
                        </div>

                        <template x-for="folder in foldersFlat" :key="'folder-' + folder.id">
                            <template x-if="isFolderVisibleInTree(folder)">
                            <div class="wc-media-collection-row"
                                 :class="{ 'is-active': tab === 'folders' && Number(currentFolderId) === Number(folder.id), 'is-drop-target': Number(dragOverFolderId) === Number(folder.id) || Number(mediaDragOverFolderId) === Number(folder.id) }"
                                 :style="`padding-left: calc(0.75rem + ${Math.max(0, Number(folder.depth || 0) + 1) * 0.9}rem)`"
                                 :draggable="renamingFolderId !== folder.id"
                                 @click="selectFolder(folder.id)"
                                 @contextmenu.prevent="openContextMenu($event, 'folder', folder)"
                                 @dragstart="startFolderDrag(folder)"
                                 @dragend="endFolderDrag()"
                                 @dragover.prevent="handleFolderTargetDragOver(folder.id)"
                                 @drop.prevent="handleFolderTargetDrop(folder)">
                                <div class="wc-media-collection-copy">
                                    <button type="button" class="wc-tree-chevron" x-show="hasFolderChildren(folder.id)" x-cloak @click.stop="toggleFolderExpanded(folder.id)" :class="{ 'is-expanded': isFolderExpanded(folder.id) }">
                                        <span class="h-3.5 w-3.5">{!! $chevronRightIcon !!}</span>
                                    </button>
                                    <span class="wc-tree-chevron-placeholder" x-show="!hasFolderChildren(folder.id)" x-cloak></span>
                                    <span class="wc-media-collection-icon">{!! $folderIcon !!}</span>
                                    <template x-if="renamingFolderId !== folder.id">
                                        <button type="button" class="wc-media-collection-title text-left" @dblclick.stop="startRenameFolder(folder)" x-text="folder.folder_name"></button>
                                    </template>
                                    <template x-if="renamingFolderId === folder.id">
                                        <div class="flex min-w-0 flex-1 items-center gap-1.5" @click.stop>
                                            <input type="text" class="input-base !rounded-md !h-9 !px-1.5 !py-1.5 !shadow-none" x-model="renameDraftTitle" @keydown.enter.prevent="saveFolderRename(folder)" @keydown.escape.prevent="cancelRename()">
                                            <button type="button" class="wc-inline-action success shrink-0" @click="saveFolderRename(folder)">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13l4 4L19 7"/></svg>
                                            </button>
                                            <button type="button" class="wc-inline-action shrink-0" @click="cancelRename()">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex items-center gap-2" x-show="renamingFolderId !== folder.id" x-cloak>
                                    <span class="wc-media-collection-count-badge" x-text="folderTreeCount(folder)"></span>
                                    <button type="button" class="wc-media-row-menu" @click.stop="openContextMenu($event, 'folder', folder)">
                                        <span class="h-4 w-4">{!! $moreIcon !!}</span>
                                    </button>
                                </div>
                            </div>
                            </template>
                        </template>

                        <div class="wc-media-inline-create" x-show="creatingFolder" x-cloak data-scroll-target>
                            <input type="text" class="input-base !rounded-lg w-full" placeholder="New folder" x-model="createFolderTitle" x-ref="folderCreateInput" :disabled="creatingFolderBusy" @keydown.enter.prevent="commitCreateFolder()" @keydown.escape.prevent="cancelCreateFolder()">
                            <div class="flex items-center gap-2">
                                <button type="button" class="wc-inline-action success" :disabled="creatingFolderBusy" :class="{ 'opacity-60 pointer-events-none': creatingFolderBusy }" @click="commitCreateFolder()">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13l4 4L19 7"/></svg>
                                </button>
                                <button type="button" class="wc-inline-action" :disabled="creatingFolderBusy" :class="{ 'opacity-60 pointer-events-none': creatingFolderBusy }" @click="cancelCreateFolder()">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div x-show="tab === 'galleries'" x-cloak>
                        <div class="wc-media-sidebar-empty" x-show="!hasGalleries && !creatingGallery" x-cloak>
                            <img src="{{ $emptyStateIcon }}" alt="" class="wc-media-empty-art">
                            <p>No galleries yet</p>
                        </div>

                        <template x-for="gallery in galleries" :key="'gallery-' + gallery.id">
                            <div class="wc-media-collection-row"
                                 :class="{ 'is-active': tab === 'galleries' && Number(activeGalleryId) === Number(gallery.id) }"
                                 @click="selectGallery(gallery.id)"
                                 @contextmenu.prevent="openContextMenu($event, 'gallery', gallery)">
                                <div class="wc-media-collection-copy">
                                    <span class="wc-media-collection-icon">{!! $galleryItemIcon !!}</span>
                                    <template x-if="renamingGalleryId !== gallery.id">
                                        <button type="button" class="wc-media-collection-title text-left" @dblclick.stop="startRenameGallery(gallery)" x-text="gallery.title"></button>
                                    </template>
                                    <template x-if="renamingGalleryId === gallery.id">
                                        <div class="flex min-w-0 flex-1 items-center gap-1.5" @click.stop>
                                            <input type="text" class="input-base !rounded-md !h-9 !px-1.5 !py-1.5 !shadow-none" x-model="renameDraftTitle" @keydown.enter.prevent="saveGalleryRename(gallery)" @keydown.escape.prevent="cancelRename()">
                                            <button type="button" class="wc-inline-action success shrink-0" @click="saveGalleryRename(gallery)">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13l4 4L19 7"/></svg>
                                            </button>
                                            <button type="button" class="wc-inline-action shrink-0" @click="cancelRename()">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex items-center gap-2" x-show="renamingGalleryId !== gallery.id" x-cloak>
                                    <span class="wc-media-collection-count-badge" x-text="gallery.items_count || 0"></span>
                                    <button type="button" class="wc-media-row-menu" @click.stop="openContextMenu($event, 'gallery', gallery)">
                                        <span class="h-4 w-4">{!! $moreIcon !!}</span>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div class="wc-media-inline-create" x-show="creatingGallery" x-cloak data-scroll-target>
                            <input type="text" class="input-base !rounded-lg w-full" placeholder="New gallery" x-model="createGalleryTitle" x-ref="galleryCreateInput" :disabled="creatingGalleryBusy" @keydown.enter.prevent="commitCreateGallery()" @keydown.escape.prevent="cancelCreateGallery()">
                            <div class="flex items-center gap-2">
                                <button type="button" class="wc-inline-action success" :disabled="creatingGalleryBusy" :class="{ 'opacity-60 pointer-events-none': creatingGalleryBusy }" @click="commitCreateGallery()">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13l4 4L19 7"/></svg>
                                </button>
                                <button type="button" class="wc-inline-action" :disabled="creatingGalleryBusy" :class="{ 'opacity-60 pointer-events-none': creatingGalleryBusy }" @click="cancelCreateGallery()">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="wc-media-main">
                <div class="wc-media-main-header">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button"
                                class="wc-media-row-menu !static !translate-x-0 !translate-y-0 shrink-0"
                                x-show="canGoUpFolder"
                                x-cloak
                                @click="goUpFolder()"
                                aria-label="Go up to parent folder"
                                title="Go up">
                            <span class="h-5 w-5">{!! $goUpIcon !!}</span>
                        </button>
                        <div class="wc-media-main-heading" x-text="tab === 'folders' ? (currentFolderName || 'Root Folder') : (currentGallery?.title || 'Galleries')"></div>
                        <div class="flex items-center gap-2" x-show="tab === 'galleries' && currentGallery" x-cloak>
                            <span class="wc-media-header-badge h-6" :class="currentGallery?.gallery_status === 'Published' ? 'is-success' : 'is-muted'">
                                <span class="h-4 w-4" x-show="currentGallery?.gallery_status === 'Published'">{!! $publishIcon !!}</span>
                                <span x-text="currentGallery?.gallery_status || 'Draft'"></span>
                            </span>
                            <span class="wc-media-header-badge is-featured" x-show="currentGallery?.is_featured" x-cloak>
                                <span class="h-4 w-4">{!! $featureIcon !!}</span>
                                <span>Featured</span>
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="wc-media-mini-button is-strong" x-show="tab === 'galleries' && currentGallery?.gallery_status === 'Draft'" x-cloak @click="setGalleryPublished(currentGallery, true)">
                            <span class="h-5 w-5">{!! $publishIcon !!}</span>
                            <span>Publish</span>
                        </button>
                        <button type="button" class="wc-media-mini-button is-strong" @click="openUploadModal()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 17c0 .93 0 1.395.102 1.776a3 3 0 0 0 2.121 2.122C5.605 21 6.07 21 7 21h10c.93 0 1.395 0 1.776-.102a3 3 0 0 0 2.122-2.122C21 18.396 21 17.93 21 17m-4.5-9.5S13.186 3 12 3S7.5 7.5 7.5 7.5M12 4v12"/></svg>
                            Upload
                        </button>
                        <button type="button" class="wc-media-mini-button" x-show="tab === 'folders'" x-cloak @click="startCreateFolder()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4l3 3h7a2 2 0 0 1 2 2v3.5M16 19h6m-3-3v6"/></svg>
                            New Folder
                        </button>
                        <button type="button" class="wc-media-mini-button" x-show="tab === 'galleries'" x-cloak @click="startCreateGallery()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M15 8h.01M12.5 21H6a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v6.5"/><path d="m3 16l5-5c.928-.893 2.072-.893 3 0l4 4"/><path d="m14 14l1-1c.67-.644 1.45-.824 2.182-.54M16 19h6m-3-3v6"/></g></svg>
                            New Gallery
                        </button>
                    </div>
                </div>

                <div class="wc-media-main-toolbar">
                    <div class="wc-media-type-filter-chips">
                        <button type="button" class="wc-media-filter-chip" :class="{ 'is-active': mediaTypeFilter === '' }" @click="mediaTypeFilter = ''">All</button>
                        <button type="button" class="wc-media-filter-chip" :class="{ 'is-active': mediaTypeFilter === 'image' }" @click="mediaTypeFilter = 'image'">Images</button>
                        <button type="button" class="wc-media-filter-chip" :class="{ 'is-active': mediaTypeFilter === 'video' }" @click="mediaTypeFilter = 'video'">Video</button>
                        <button type="button" class="wc-media-filter-chip" :class="{ 'is-active': mediaTypeFilter === 'document' }" @click="mediaTypeFilter = 'document'">Docs</button>
                    </div>

                    <div class="wc-media-toolbar-tools">
                        <div class="relative wc-media-search-wrap">
                            <input type="text" class="input-base wc-media-search-input" x-model.debounce.150ms="searchText" placeholder="Search">
                        </div>
                        <div class="wc-media-toggle-group">
                            <button type="button" class="wc-media-icon-toggle" :class="{ 'is-active': mediaSort === 'modified' }" @click="setMediaSort('modified')" title="Sort by modified">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </button>
                            <button type="button" class="wc-media-icon-toggle" :class="{ 'is-active': mediaSort === 'name' }" @click="setMediaSort('name')" title="Sort by name">A</button>
                        </div>
                        <div class="wc-media-toggle-group">
                            <button type="button" class="wc-media-size-toggle" :class="{ 'is-active': thumb === 'sm' }" @click="setThumb('sm')" title="Small">
                                <span class="h-4 w-4">{!! $sizeSmallIcon !!}</span>
                            </button>
                            <button type="button" class="wc-media-size-toggle" :class="{ 'is-active': thumb === 'md' }" @click="setThumb('md')" title="Medium">
                                <span class="h-4 w-4">{!! $sizeMediumIcon !!}</span>
                            </button>
                            <button type="button" class="wc-media-size-toggle" :class="{ 'is-active': thumb === 'lg' }" @click="setThumb('lg')" title="Large">
                                <span class="h-4 w-4">{!! $sizeLargeIcon !!}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="wc-media-bulkbar" x-show="checkedIds.length > 0" x-cloak>
                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        <button type="button" class="wc-media-mini-button" @click="selectAllVisible()">Select all</button>
                        <button type="button" class="wc-media-mini-button" @click="clearChecked()">Deselect all</button>
                        <button type="button" class="wc-media-mini-button danger" @click="confirmDeleteChecked()">Delete selected</button>
                        <button type="button" class="wc-media-mini-button" @click="openAddToGalleryModal(checkedIds)">Add to gallery</button>
                        <button type="button" class="wc-media-mini-button" x-show="tab === 'galleries'" x-cloak @click="removeCheckedFromCurrentGallery()">Remove from gallery</button>
                    </div>
                    <div class="truncate text-xs text-gray-500" x-text="`${checkedIds.length} selected`"></div>
                </div>

                <div class="wc-media-grid-scroll custom-scrollbar">
                    <div class="wc-media-loading" x-show="loadingPane" x-cloak>
                        <div class="wc-media-loading-spinner"></div>
                    </div>
                    <div class="wc-media-folder-section" x-show="!loadingPane && tab === 'folders' && currentSubfolders().length > 0" x-cloak>
                        <div class="wc-media-folder-grid">
                            <template x-for="folder in currentSubfolders()" :key="'subfolder-' + folder.id">
                                <button type="button"
                                        class="wc-media-folder-card"
                                        :class="{ 'is-drop-target': Number(mediaDragOverSubfolderId) === Number(folder.id) }"
                                        @dblclick.prevent="selectFolder(folder.id)"
                                        @dragover.prevent="setMediaSubfolderDropTarget(folder.id)"
                                        @dragleave="clearMediaDropTarget()"
                                        @drop.prevent="dropMediaOnFolder(folder)"
                                        :title="`Open ${folder.folder_name}`">
                                    <span class="wc-media-folder-card-icon">{!! $folderIcon !!}</span>
                                    <span class="wc-media-folder-card-title" x-text="folder.folder_name"></span>
                                    <span class="wc-media-folder-card-meta" x-text="folderCardMeta(folder)"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div class="wc-media-grid" :style="gridStyle" x-show="!showNoGalleryState" x-cloak>
                        <template x-for="item in filteredItems()" :key="'media-' + item.id">
                            <article class="wc-media-item group"
                                     :style="itemCardStyle"
                                     :class="{ 'is-selected': Number(selectedId) === Number(item.id), 'is-checked': checkedIds.includes(Number(item.id)), 'is-dragging': Number(draggingMediaId) === Number(item.id) }"
                                     :draggable="renamingMediaId !== item.id"
                                     @click="selectItem(item.id)"
                                     @dragstart="startMediaDrag(item, $event)"
                                     @dragend="endMediaDrag()"
                                     @contextmenu.prevent="openContextMenu($event, 'media', item)">
                                <div class="wc-media-item-controls">
                                    <label class="wc-media-check">
                                        <input type="checkbox" :checked="checkedIds.includes(Number(item.id))" @click.stop="toggleChecked(item.id)">
                                    </label>
                                    <button type="button" class="wc-media-row-menu" @click.stop="openContextMenu($event, 'media', item)">
                                        <span class="h-4 w-4">{!! $moreIcon !!}</span>
                                    </button>
                                </div>

                                <button type="button"
                                        class="wc-media-thumb"
                                        :class="{ 'is-previewable': isPreviewableMedia(item) }"
                                        :style="thumbStyle"
                                        :title="isPreviewableMedia(item) ? 'Preview media' : 'Preview unavailable'"
                                        @dblclick.stop="openLightbox(item)">
                                    <template x-if="item.media_type === 'image' && (item.full_url || item.public_url)">
                                        <img :src="item.thumbnail_full_url || item.thumbnail_url || item.full_url || item.public_url" :alt="item.alt_text || item.title || item.original_name || 'Media item'" class="h-full w-full object-cover" draggable="false">
                                    </template>
                                    <template x-if="item.media_type !== 'image' || !(item.full_url || item.public_url)">
                                        <div class="wc-media-thumb-placeholder">
                                            <template x-if="item.media_type === 'video'">
                                                <span class="h-12 w-12">{!! $videoIcon !!}</span>
                                            </template>
                                            <template x-if="item.media_type === 'document'">
                                                <span class="h-12 w-12">{!! $documentIcon !!}</span>
                                            </template>
                                            <template x-if="item.media_type !== 'video' && item.media_type !== 'document'">
                                                <span class="h-12 w-12">{!! $imageIcon !!}</span>
                                            </template>
                                        </div>
                                    </template>
                                </button>

                                <div class="wc-media-title-wrap" :style="titleBlockStyle">
                                    <template x-if="renamingMediaId !== item.id">
                                        <button type="button" class="wc-media-title" @dblclick.stop="startRenameMedia(item)" x-text="item.title || item.original_name || 'Untitled'"></button>
                                    </template>
                                    <template x-if="renamingMediaId === item.id">
                                        <input type="text"
                                               class="input-base wc-media-title-input !h-9 !rounded-md !px-2 !py-1.5 !shadow-none"
                                               x-model="renameDraftTitle"
                                               :data-media-rename-id="item.id"
                                               @blur="saveMediaRename(item)"
                                               @keydown.enter.prevent="saveMediaRename(item)"
                                               @keydown.escape.prevent="cancelRename()">
                                    </template>
                                </div>
                            </article>
                        </template>
                    </div>

                    <div x-show="!loadingPane && showNoGalleryState" x-cloak class="wc-media-empty">
                        <img src="{{ $emptyStateIcon }}" alt="" class="wc-media-empty-art">
                        <p>No galleries yet</p>
                        <button type="button" class="wc-media-mini-button is-strong" @click="startCreateGallery()">Add a gallery</button>
                    </div>

                    <div x-show="showFilterEmptyState" x-cloak class="wc-media-empty">
                        <img src="{{ $emptyStateIcon }}" alt="" class="wc-media-empty-art">
                        <p>No media matches your search or filter criteria</p>
                    </div>

                    <div x-show="!loadingPane && !showNoGalleryState && !showFilterEmptyState && filteredItems().length === 0 && currentSubfolders().length === 0" x-cloak class="wc-media-empty">
                        <img src="{{ $emptyStateIcon }}" alt="" class="wc-media-empty-art">
                        <p x-text="tab === 'galleries' ? 'No media in this gallery' : 'No items found'"></p>
                    </div>

                </div>

                <div class="wc-media-statusbar">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="min-w-0 truncate text-[13px] text-gray-600" x-show="selectedItemSummary  && !statusMessage" x-text="selectedItemSummary"></div>
                        <div class="wc-media-status-message" x-show="statusMessage && !showFilterEmptyState" x-cloak :class="`is-${statusTone}`">
                            <svg x-show="statusTone === 'success'" class="h-4 w-4 mr-2 mb-[-1px]" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12h10M7 8h6"/><path d="M3 20.29V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H7.961a2 2 0 0 0-1.561.75l-2.331 2.914A.6.6 0 0 1 3 20.29Z"/></g></svg>
                            <svg x-show="statusTone === 'warning'" class="h-4 w-4 mr-2 mb-[-1px]" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v2m0 4.01l.01-.011"/><path d="M3 20.29V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H7.961a2 2 0 0 0-1.561.75l-2.331 2.914A.6.6 0 0 1 3 20.29Z"/></g></svg>
                            <svg x-show="statusTone === 'error'" class="h-4 w-4 mr-2 mb-[-1px]" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v2m0 4.01l.01-.011"/><path d="M3 20.29V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H7.961a2 2 0 0 0-1.561.75l-2.331 2.914A.6.6 0 0 1 3 20.29Z"/></g></svg>
                            <span x-text="statusMessage"></span>
                        </div>
                    </div>
                    <div class="truncate text-xs text-gray-500" x-text="statusSummary"></div>
                </div>
            </section>
        </div>

        <div x-show="contextMenu.open" x-cloak class="wc-context-menu-wrap" :style="`left:${contextMenu.x}px; top:${contextMenu.y}px`" @mousedown.outside="closeAllMenus()">
        <div class="wc-context-menu">
            <template x-if="contextMenu.type === 'folder'">
                <div class="wc-context-menu-list">
                    <button type="button" class="wc-context-menu-item" @click="startRenameFolder(contextMenu.target); closeContextMenu();">
                        <span class="wc-context-menu-copy"><span class="wc-context-menu-icon">{!! $renameIcon !!}</span><span>Rename</span></span>
                    </button>
                    <button type="button" class="wc-context-menu-item" @click="openFolderProperties(contextMenu.target); closeContextMenu();">
                        <span class="wc-context-menu-copy"><span class="wc-context-menu-icon">{!! $propertiesIcon !!}</span><span>Properties</span></span>
                    </button>
                    <button type="button" class="wc-context-menu-item danger" @click="openFolderDeleteModal(contextMenu.target); closeContextMenu();">
                        <span class="wc-context-menu-copy"><span class="wc-context-menu-icon">{!! $deleteIcon !!}</span><span>Delete</span></span>
                    </button>
                </div>
            </template>
            <template x-if="contextMenu.type === 'gallery'">
                <div class="wc-context-menu-list">
                    <button type="button" class="wc-context-menu-item" @click="startRenameGallery(contextMenu.target); closeContextMenu();">
                        <span class="wc-context-menu-copy"><span class="wc-context-menu-icon">{!! $renameIcon !!}</span><span>Rename</span></span>
                    </button>
                    <button type="button" class="wc-context-menu-item" @click="openGalleryProperties(contextMenu.target); closeContextMenu();">
                        <span class="wc-context-menu-copy"><span class="wc-context-menu-icon">{!! $propertiesIcon !!}</span><span>Properties</span></span>
                    </button>
                    <button type="button" class="wc-context-menu-item" x-show="contextMenu.target?.gallery_status !== 'Published'" x-cloak @click="setGalleryPublished(contextMenu.target, true); closeContextMenu();">
                        <span class="wc-context-menu-copy"><span class="wc-context-menu-icon">{!! $publishIcon !!}</span><span>Publish</span></span>
                    </button>
                    <button type="button" class="wc-context-menu-item" x-show="contextMenu.target?.gallery_status === 'Published'" x-cloak @click="setGalleryPublished(contextMenu.target, false); closeContextMenu();">
                        <span class="wc-context-menu-copy"><span class="wc-context-menu-icon">{!! $unpublishIcon !!}</span><span>Unpublish</span></span>
                    </button>
                    <button type="button" class="wc-context-menu-item danger" @click="deleteGallery(contextMenu.target); closeContextMenu();">
                        <span class="wc-context-menu-copy"><span class="wc-context-menu-icon">{!! $deleteIcon !!}</span><span>Delete</span></span>
                    </button>
                </div>
            </template>
            <template x-if="contextMenu.type === 'media'">
                <div class="wc-context-menu-list">
                    <button type="button" class="wc-context-menu-item" @click="startRenameMedia(contextMenu.target); closeContextMenu();">
                        <span class="wc-context-menu-copy"><span class="wc-context-menu-icon">{!! $renameIcon !!}</span><span>Rename</span></span>
                    </button>
                    <button type="button" class="wc-context-menu-item" @click="openMediaProperties(contextMenu.target); closeContextMenu();">
                        <span class="wc-context-menu-copy"><span class="wc-context-menu-icon">{!! $propertiesIcon !!}</span><span>Properties</span></span>
                    </button>
                    <button type="button" class="wc-context-menu-item has-submenu" x-show="isGalleryEligibleMedia(contextMenu.target)" x-cloak @click.stop="openContextSubmenu('gallery')">
                        <span class="wc-context-menu-copy"><span class="wc-context-menu-icon">{!! $galleryAddIcon !!}</span><span>Add to gallery</span></span>
                        <span class="wc-context-menu-chevron">›</span>
                    </button>
                    <button type="button" class="wc-context-menu-item" x-show="tab === 'galleries'" x-cloak @click="removeMediaFromCurrentGallery(contextMenu.target); closeContextMenu();">
                        <span class="wc-context-menu-copy"><span class="wc-context-menu-icon">{!! $removeIcon !!}</span><span>Remove from gallery</span></span>
                    </button>
                    <button type="button" class="wc-context-menu-item has-submenu" @click.stop="openContextSubmenu('folder')">
                        <span class="wc-context-menu-copy"><span class="wc-context-menu-icon">{!! $moveToFolderIcon !!}</span><span>Move to folder</span></span>
                        <span class="wc-context-menu-chevron">›</span>
                    </button>
                    <button type="button" class="wc-context-menu-item" @click="downloadMedia(contextMenu.target); closeContextMenu();">
                        <span class="wc-context-menu-copy"><span class="wc-context-menu-icon">{!! $downloadIcon !!}</span><span>Download</span></span>
                    </button>
                    <button type="button" class="wc-context-menu-item danger" @click="deleteMedia(contextMenu.target); closeContextMenu();">
                        <span class="wc-context-menu-copy"><span class="wc-context-menu-icon">{!! $deleteIcon !!}</span><span>Delete</span></span>
                    </button>
                </div>
            </template>
        </div>
        <template x-if="contextMenu.open && contextMenu.type === 'media' && contextMenu.submenu === 'gallery'">
        <div class="wc-context-submenu" :style="contextSubmenuStyle">
            <div class="wc-context-menu-list custom-scrollbar">
                <template x-for="gallery in contextGalleryTargets()" :key="'ctx-gallery-' + gallery.id">
                    <button type="button" class="wc-context-menu-item" @click="addMediaIdsToGallery([Number(contextMenu.target.id)], gallery.id).then(async (data) => { notify(data.message || 'Media added to gallery successfully.', 'success', { toast: false }); closeAllMenus(); if (tab === 'galleries' && Number(activeGalleryId) === Number(gallery.id)) { await fetchWorkspace({ tab: 'galleries', gallery_id: activeGalleryId }); } }).catch((error) => notify(error.message, 'error'));">
                        <span class="wc-context-menu-copy">
                            <span class="wc-context-menu-icon">{!! $galleryAddIcon !!}</span>
                            <span class="truncate" x-text="gallery.title"></span>
                        </span>
                    </button>
                </template>
            </div>
        </div>
        </template>
        <template x-if="contextMenu.open && contextMenu.type === 'media' && contextMenu.submenu === 'folder'">
        <div class="wc-context-submenu" :style="contextSubmenuStyle">
            <div class="wc-context-menu-list custom-scrollbar">
                <template x-for="folder in contextFolderTargets()" :key="'ctx-folder-' + (folder.id || 'root')">
                    <button type="button" class="wc-context-menu-item" @click="moveMediaToFolder(contextMenu.target, folder.id); closeAllMenus();">
                        <span class="wc-context-menu-copy">
                            <span class="wc-context-menu-icon" x-html="folder.id === '' ? @js($folderOpenIcon) : @js($folderIcon)"></span>
                            <span class="truncate" x-text="folder.id === '' ? 'Root Folder' : contextFolderLabel(folder)"></span>
                        </span>
                    </button>
                </template>
            </div>
        </div>
        </template>
        </div>
    </div>

    <template x-teleport="body">
        <aside
            x-show="mediaProperties.open && mediaProperties.surface === 'pane'"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-8 opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-8 opacity-0"
            class="wc-media-properties-pane"
        >
            <div class="wc-media-properties-pane-head">
                <button type="button" class="wc-media-properties-close" @click="closeMediaProperties()" aria-label="Collapse properties">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6l6 6l-6 6"/></svg>
                </button>
                <div class="min-w-0">
                    <div class="wc-media-properties-title">Media Properties</div>
                    <div class="wc-media-properties-name" x-text="mediaProperties.form.original_name || mediaProperties.form.title || 'Media item'"></div>
                </div>
            </div>

            <div class="wc-media-properties-pane-body custom-scrollbar">
                <div class="wc-media-properties-summary">
                    <div class="wc-media-properties-preview h-16 w-16 shrink-0">
                        <template x-if="mediaProperties.form.preview_url && mediaProperties.form.media_type === 'image'">
                            <img :src="mediaProperties.form.preview_url" alt="" class="h-full w-full object-cover">
                        </template>
                        <template x-if="!mediaProperties.form.preview_url || mediaProperties.form.media_type !== 'image'">
                            <div class="wc-media-thumb-placeholder">
                                <template x-if="mediaProperties.form.media_type === 'video'">
                                    <span class="h-8 w-8">{!! $videoIcon !!}</span>
                                </template>
                                <template x-if="mediaProperties.form.media_type === 'document'">
                                    <span class="h-8 w-8">{!! $documentIcon !!}</span>
                                </template>
                                <template x-if="mediaProperties.form.media_type !== 'video' && mediaProperties.form.media_type !== 'document'">
                                    <span class="h-8 w-8">{!! $imageIcon !!}</span>
                                </template>
                            </div>
                        </template>
                    </div>

                    <div class="wc-media-properties-meta min-w-0 flex-1">
                        <div class="break-all font-medium text-[var(--text-strong)]" x-text="mediaProperties.form.original_name || 'Media item'"></div>
                        <div class="mt-1 text-[11px]" x-text="mediaProperties.form.mime_type || ''"></div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="label-base">Title</label>
                        <input type="text" class="input-base wc-media-pane-input w-full" x-model="mediaProperties.form.title">
                    </div>
                    <div>
                        <label class="label-base">Folder</label>
                        <select class="select-base wc-media-pane-input w-full" x-model="mediaProperties.form.folder_id">
                            <option value="">Root Folder</option>
                            <template x-for="folder in foldersFlat" :key="'media-pane-folder-' + folder.id">
                                <option :value="folder.id" x-text="folderLabel(folder)"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="label-base">Alt Text</label>
                        <input type="text" class="input-base wc-media-pane-input w-full" x-model="mediaProperties.form.alt_text">
                    </div>
                    <div>
                        <label class="label-base">Caption</label>
                        <textarea rows="1" class="textarea-base wc-media-pane-input w-full" x-model="mediaProperties.form.caption"></textarea>
                    </div>
                    <div>
                        <label class="label-base">Description</label>
                        <textarea rows="2" class="textarea-base wc-media-pane-input w-full" x-model="mediaProperties.form.description"></textarea>
                    </div>
                </div>
            </div>

            <div class="wc-media-properties-pane-foot">
                <button type="button" class="btn btn-outline btn-sm" @click="closeMediaProperties()">Close</button>
                <button type="button" class="btn btn-primary btn-sm" :disabled="mediaProperties.busy" @click="saveMediaProperties()">
                    <span x-text="mediaProperties.busy ? 'Saving...' : 'Save'"></span>
                </button>
            </div>
        </aside>
    </template>

    <template x-teleport="body">
        <div
            x-show="lightbox.open && lightboxItem"
            x-cloak
            x-transition.opacity
            class="wc-media-lightbox"
            @click.self="closeLightbox()"
            @wheel="handleLightboxWheel($event)"
        >
            <div class="wc-media-lightbox-shell">
                <div class="wc-media-lightbox-stage">
                    <div class="wc-media-lightbox-toolbar">
                        <div class="wc-media-lightbox-toolbar-side is-left" :class="{ 'is-hidden': lightboxItem?.media_type !== 'image' }">
                            <button type="button" class="wc-media-lightbox-tool" @click="adjustLightboxZoom(-0.2)" aria-label="Zoom out">−</button>
                            <button type="button" class="wc-media-lightbox-tool is-label" @click="resetLightboxZoom()" x-text="`${Math.round((lightbox.zoom || 1) * 100)}%`"></button>
                            <button type="button" class="wc-media-lightbox-tool" @click="adjustLightboxZoom(0.2)" aria-label="Zoom in">+</button>
                        </div>
                        <div class="wc-media-lightbox-toolbar-side is-right">
                            <button type="button" class="wc-media-lightbox-tool" :disabled="lightboxItems.length < 2" @click="showPreviousLightboxItem()" aria-label="Previous media">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18l-6-6l6-6"/></svg>
                            </button>
                            <button type="button" class="wc-media-lightbox-tool" :disabled="lightboxItems.length < 2" @click="showNextLightboxItem()" aria-label="Next media">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18l6-6l-6-6"/></svg>
                            </button>
                            <button type="button" class="wc-media-lightbox-tool" @click="closeLightbox()" aria-label="Close preview">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="wc-media-lightbox-canvas" x-ref="lightboxCanvas">
                        <template x-if="lightboxItem?.media_type === 'image'">
                            <div
                                class="wc-media-lightbox-image-wrap"
                                :class="{ 'is-pannable': (lightbox.zoom || 1) > 1, 'is-panning': lightbox.panning }"
                                @mousedown.prevent="startLightboxPan($event)"
                                @mousemove.prevent="moveLightboxPan($event)"
                                @mouseup="endLightboxPan()"
                                @mouseleave="endLightboxPan()"
                            >
                                <img
                                    x-ref="lightboxImage"
                                    :src="lightboxItem.full_url || lightboxItem.public_url"
                                    :alt="lightboxItem.alt_text || lightboxDisplayTitle(lightboxItem)"
                                    class="wc-media-lightbox-image"
                                    @load="handleLightboxImageLoad()"
                                    :style="`width:${lightbox.fitWidth ? `${lightbox.fitWidth}px` : 'auto'};height:${lightbox.fitHeight ? `${lightbox.fitHeight}px` : 'auto'};transform: translate3d(${lightbox.panX || 0}px, ${lightbox.panY || 0}px, 0) scale(${lightbox.zoom || 1})`"
                                >
                            </div>
                        </template>

                        <template x-if="lightboxItem?.media_type === 'video'">
                            <video
                                :src="lightboxItem.full_url || lightboxItem.public_url"
                                class="wc-media-lightbox-video"
                                controls
                                playsinline
                            ></video>
                        </template>

                        <template x-if="lightboxItem?.media_type === 'document'">
                            <iframe
                                :src="lightboxItem.full_url || lightboxItem.public_url"
                                class="wc-media-lightbox-pdf"
                                title="PDF preview"
                            ></iframe>
                        </template>
                    </div>
                </div>

                <aside class="wc-media-lightbox-info custom-scrollbar">
                    <div class="wc-media-lightbox-title" x-text="lightboxDisplayTitle(lightboxItem)"></div>
                    <div class="wc-media-lightbox-badges">
                        <span class="wc-media-lightbox-badge" x-text="String(lightboxItem?.media_type || 'media').replace(/^./, (letter) => letter.toUpperCase())"></span>
                        <span class="wc-media-lightbox-badge" x-show="lightboxItem?.file_size" x-cloak x-text="`${Math.max(1, Math.round((lightboxItem?.file_size || 0) / 1024))} KB`"></span>
                        <span class="wc-media-lightbox-badge" x-show="lightboxItem?.width && lightboxItem?.height" x-cloak x-text="`${lightboxItem?.width}×${lightboxItem?.height}`"></span>
                    </div>

                    <dl class="wc-media-lightbox-meta">
                        <div>
                            <dt>Name</dt>
                            <dd class="break-all" x-text="lightboxItem?.original_name || lightboxItem?.file_name || 'Media item'"></dd>
                        </div>
                        <div x-show="lightboxItem?.mime_type" x-cloak>
                            <dt>Type</dt>
                            <dd class="break-all" x-text="lightboxItem?.mime_type"></dd>
                        </div>
                        <div x-show="lightboxItem?.caption || lightboxItem?.gallery_caption_override" x-cloak>
                            <dt>Caption</dt>
                            <dd x-text="lightboxItem?.gallery_caption_override || lightboxItem?.caption"></dd>
                        </div>
                    </dl>

                    <div class="wc-media-lightbox-description" x-show="lightboxDescription(lightboxItem)" x-cloak>
                        <div class="wc-media-lightbox-section-title">Description</div>
                        <p x-text="lightboxDescription(lightboxItem)"></p>
                    </div>

                    <div class="pt-2">
                        <a class="wc-media-lightbox-link" :href="lightboxItem?.full_url || lightboxItem?.public_url" target="_blank" rel="noopener noreferrer">Open original</a>
                    </div>
                </aside>
            </div>
        </div>
    </template>

    @include('web_curator::media.partials.workspace-modals', [
        'folderIcon' => $folderIcon,
        'imageIcon' => $imageIcon,
        'videoIcon' => $videoIcon,
        'documentIcon' => $documentIcon,
    ])
</div>
@endsection
