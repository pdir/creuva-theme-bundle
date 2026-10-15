var e={de:{kicker:`Vorschau`,title:[`Überschrift`,`mit Akzent`],text:[`Fließtext mit`,`einem Link`,`und`,`gedämpftem Text`],button:`Button`,secondary:`Sekundär`,badge:`Beliebt`,soft:[`Hinweisbox`,`Akzent-Hintergrund`],primary:[`Box`,`Primär`,`Text auf der Primärfarbe`]},en:{kicker:`Preview`,title:[`Headline`,`with accent`],text:[`Body text with`,`a link`,`and`,`muted text`],button:`Button`,secondary:`Secondary`,badge:`Popular`,soft:[`Info box`,`Accent background`],primary:[`Box`,`primary`,`Text on the primary color`]}},t=/^[0-9a-f]{6}$/i,n=[`primary`,`accent`,`accent-text`,`accent-contrast`,`dark-accent`],r=e=>`
  <div class="creuva-preview" aria-hidden="true">
    <p class="creuva-preview__kicker">${e.kicker}</p>
    <p class="creuva-preview__title">${e.title[0]} <span>${e.title[1]}</span></p>
    <p class="creuva-preview__text">${e.text[0]} <u>${e.text[1]}</u> ${e.text[2]} <span>${e.text[3]}</span>.</p>
    <p class="creuva-preview__actions">
      <span class="creuva-preview__button">${e.button}</span>
      <span class="creuva-preview__button creuva-preview__button--secondary">${e.secondary}</span>
      <span class="creuva-preview__badge">${e.badge}</span>
    </p>
    <div class="creuva-preview__cards">
      <div class="creuva-preview__card creuva-preview__card--soft"><strong>${e.soft[0]}</strong>${e.soft[1]}</div>
      <div class="creuva-preview__card creuva-preview__card--primary"><strong>${e.primary[0]} <span>${e.primary[1]}</span></strong>${e.primary[2]}</div>
    </div>
  </div>`,i=()=>{let i=document.querySelector(`.creuva-scheme-picker`),a=i?.closest(`form`);if(!a||i.dataset.creuvaPreview)return;i.dataset.creuvaPreview=`true`;let o=document.createElement(`div`);o.className=`widget clr`,o.innerHTML=r(e[document.documentElement.lang?.startsWith(`de`)?`de`:`en`]),i.after(o);let s=o.firstElementChild,c=e=>a.querySelector(`[name="${e}"]`)?.value.trim().replace(/^#/,``)??``,l=()=>{let e=a.querySelector(`input[name="creuvaScheme"]:checked`)?.value;e?s.dataset.scheme=e:delete s.dataset.scheme,s.dataset.theme=c(`creuvaVariant`)||`light`,s.dataset.radius=c(`creuvaRadius`)||`large`,n.forEach(e=>s.style.removeProperty(`--scheme-${e}`));let r=c(`creuvaPrimaryColor`),i=c(`creuvaAccentColor`);t.test(r)&&s.style.setProperty(`--scheme-primary`,`#${r}`),t.test(i)&&(s.style.setProperty(`--scheme-accent`,`#${i}`),s.style.setProperty(`--scheme-accent-text`,`#${i}`),s.style.setProperty(`--scheme-accent-contrast`,`#fff`),s.style.setProperty(`--scheme-dark-accent`,`color-mix(in srgb, #${i} 60%, #fff)`))};a.addEventListener(`change`,l),a.addEventListener(`input`,l),l()};document.addEventListener(`turbo:load`,i),document.addEventListener(`DOMContentLoaded`,i),document.readyState!==`loading`&&i();