
function todayDate() {
  const d = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function toDateString(dateObj) {
  if (!(dateObj instanceof Date) || Number.isNaN(dateObj.getTime())) {
    return '';
  }

  const pad = (n) => String(n).padStart(2, '0');
  return `${dateObj.getFullYear()}-${pad(dateObj.getMonth() + 1)}-${pad(dateObj.getDate())}`;
}

function parseDateString(value) {
  const [year, month, day] = String(value || '').split('-').map(Number);
  if (!year || !month || !day) return null;
  return new Date(year, month - 1, day);
}

function startOfMonth(dateObj) {
  return new Date(dateObj.getFullYear(), dateObj.getMonth(), 1);
}

function addMonths(dateObj, months) {
  return new Date(dateObj.getFullYear(), dateObj.getMonth() + months, 1);
}

function asMinutes(value) {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }

  if (typeof value === 'string') {
    const parsed = Number(value);
    if (Number.isFinite(parsed)) {
      return parsed;
    }
  }

  return null;
}

function getNextUpcomingLoad(loads) {
  if (!Array.isArray(loads) || loads.length === 0) {
    return null;
  }

  const withMinutes = loads
    .map((load) => ({ load, minutes: asMinutes(load?.minutesUntil) }))
    .filter((item) => Number.isFinite(item.minutes) && item.minutes >= 0)
    .sort((a, b) => a.minutes - b.minutes);

  if (withMinutes.length > 0) {
    return withMinutes[0].load;
  }

  return loads[0] || null;
}

function urgencyClass(minutes) {
  const m = parseInt(minutes, 10);
  if (Number.isNaN(m) || m < 0) return '';
  if (m <= 5) return ' skyview-time-left--urgent';
  if (m <= 15) return ' skyview-time-left--warning';
  if (m <= 20) return ' skyview-time-left--soon';
  return ' skyview-time-left--ok';
}

function createEl(tag, className, text) {
  const el = document.createElement(tag);
  if (className) {
    el.className = className;
  }
  if (text !== undefined && text !== null) {
    el.textContent = String(text);
  }
  return el;
}

function formatDisplayName(name, showQuotedNameParts) {
  const text = String(name || '').trim();
  if (showQuotedNameParts || text === '') {
    return text;
  }

  const withoutQuoted = text
    .replace(/"[^"]*"/g, '')
    .replace(/'[^']*'/g, '')
    .replace(/\s{2,}/g, ' ')
    .trim();

  return withoutQuoted || text;
}

var _audioCtx = null;
function getAudioCtx() {
  if (!_audioCtx) {
    _audioCtx = new (window.AudioContext || window.webkitAudioContext)();
  }
  if (_audioCtx.state === 'suspended') _audioCtx.resume();
  return _audioCtx;
}

// Try to unlock audio context early; also unlock on first interaction.
try { getAudioCtx(); } catch (_) {}
['click', 'touchstart', 'keydown', 'scroll'].forEach(function (evt) {
  document.addEventListener(evt, function () {
    try { getAudioCtx(); } catch (_) {}
  }, { once: true, passive: true });
});

function playPingSound() {
  try {
    var ctx = getAudioCtx();
    var osc = ctx.createOscillator();
    var gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.type = 'sine';
    osc.frequency.setValueAtTime(880, ctx.currentTime);
    gain.gain.setValueAtTime(0.3, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.5);
  } catch (_) { /* ignore */ }
}

function createSettingsIcon() {
  const NS = 'http://www.w3.org/2000/svg';
  const svg = document.createElementNS(NS, 'svg');
  svg.setAttribute('class', 'skyview-settings-icon');
  svg.setAttribute('viewBox', '0 0 20 20');
  svg.setAttribute('aria-hidden', 'true');

  const outer = document.createElementNS(NS, 'path');
  outer.setAttribute('fill', 'none');
  outer.setAttribute('stroke', 'currentColor');
  outer.setAttribute('stroke-width', '1.8');
  outer.setAttribute('stroke-linecap', 'round');
  outer.setAttribute('stroke-linejoin', 'round');
  outer.setAttribute('d', 'M8.5 2h3l.4 2.2a5.5 5.5 0 0 1 1.8 1l2.1-.7 1.5 2.6-1.7 1.5a5.6 5.6 0 0 1 0 2.1l1.7 1.5-1.5 2.6-2.1-.7a5.5 5.5 0 0 1-1.8 1L11.5 18h-3l-.4-2.2a5.5 5.5 0 0 1-1.8-1l-2.1.7-1.5-2.6 1.7-1.5a5.6 5.6 0 0 1 0-2.1L2.7 7.8l1.5-2.6 2.1.7a5.5 5.5 0 0 1 1.8-1z');

  const inner = document.createElementNS(NS, 'circle');
  inner.setAttribute('cx', '10');
  inner.setAttribute('cy', '10');
  inner.setAttribute('r', '2.3');
  inner.setAttribute('fill', 'none');
  inner.setAttribute('stroke', 'currentColor');
  inner.setAttribute('stroke-width', '1.8');

  svg.appendChild(outer);
  svg.appendChild(inner);
  return svg;
}

function createCalendarIcon() {
  const NS = 'http://www.w3.org/2000/svg';
  const svg = document.createElementNS(NS, 'svg');
  svg.setAttribute('class', 'skyview-calendar-icon');
  svg.setAttribute('viewBox', '0 0 20 20');
  svg.setAttribute('aria-hidden', 'true');

  const p = document.createElementNS(NS, 'path');
  p.setAttribute('fill', 'none');
  p.setAttribute('stroke', 'currentColor');
  p.setAttribute('stroke-width', '1.6');
  p.setAttribute('stroke-linecap', 'round');
  p.setAttribute('stroke-linejoin', 'round');
  p.setAttribute('d', 'M3 5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5zM7 1v4M13 1v4M3 8h14');

  svg.appendChild(p);
  return svg;
}

function createTvIcon() {
  const NS = 'http://www.w3.org/2000/svg';
  const svg = document.createElementNS(NS, 'svg');
  svg.setAttribute('class', 'skyview-tv-icon');
  svg.setAttribute('viewBox', '0 0 20 20');
  svg.setAttribute('aria-hidden', 'true');

  const p = document.createElementNS(NS, 'path');
  p.setAttribute('fill', 'none');
  p.setAttribute('stroke', 'currentColor');
  p.setAttribute('stroke-width', '1.6');
  p.setAttribute('stroke-linecap', 'round');
  p.setAttribute('stroke-linejoin', 'round');
  p.setAttribute('d', 'M6 2l4 4M14 2l-4 4M2 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8zM7 17h6');

  svg.appendChild(p);
  return svg;
}

function buildRenderSegments(jumpers) {
  const segments = [];
  let i = 0;

  while (i < jumpers.length) {
    const jumper = jumpers[i];

    if (!jumper.group_id) {
      segments.push({ type: 'single', jumper });
      i += 1;
      continue;
    }

    const groupId = jumper.group_id;
    const groupTitle = jumper.group_title || groupId;
    const members = [];

    while (i < jumpers.length && jumpers[i].group_id === groupId) {
      members.push(jumpers[i]);
      i += 1;
    }

    segments.push({ type: 'group', groupId, groupTitle, members });
  }

  return segments;
}

function segmentAltitude(seg) {
  const raw = seg.type === 'single'
    ? String(seg.jumper.altitude || '')
    : String((seg.members[0] || {}).altitude || '');
  const m = raw.match(/\d+/);
  return m ? parseInt(m[0], 10) : Infinity;
}

function segmentAltitudeText(seg) {
  return seg.type === 'single'
    ? String(seg.jumper.altitude || '').trim()
    : String((seg.members[0] || {}).altitude || '').trim();
}

function buildGroupColorMap(jumpers) {
  const map = {};
  let colorIndex = 1;
  const colorCount = 10;

  for (const jumper of jumpers) {
    const groupId = jumper?.group_id;
    if (!groupId || map[groupId]) {
      continue;
    }

    map[groupId] = colorIndex;
    colorIndex = (colorIndex % colorCount) + 1;
  }

  return map;
}

function renderJumperRow(jumper, groupColor, compact, showQuotedNameParts, isChief = false, showAltitudeInline = false) {
  const row = createEl('div', 'skyview-jumper-row');
  if (isChief) {
    row.classList.add('skyview-jumper-row--chief');
  }
  if (jumper.group_id) {
    row.classList.add('skyview-jumper-row--group');
    if (groupColor > 0) {
      row.classList.add(`skyview-jumper-row--group-${groupColor}`);
    }
  }

  if (jumper.captain) {
    row.classList.add('skyview-jumper-row--captain');
  }

  const jumpTypeText = String(jumper.jump_type_name || jumper.jump_type || '').trim();
  const jumpTypeKey = jumpTypeText.toLowerCase().replace(/[^a-z0-9]/g, '');
  if (jumpTypeKey) {
    row.classList.add(`skyview-jumper-row--type-${jumpTypeKey}`);
  }

  const altitudeText = String(jumper.altitude || '').trim();
  const studentJumpNo = jumper.student_jump_no != null ? String(jumper.student_jump_no) : '';

  // Queue items (showAltitudeInline) use skyText as label — show as-is
  const labelText = formatDisplayName(jumper.label || '', showQuotedNameParts);
  const label = createEl('span', 'skyview-jumper-label', labelText);

  if (!showAltitudeInline && !compact && (jumpTypeText || studentJumpNo)) {
    const meta = createEl('span', 'skyview-jumper-meta');
    if (jumpTypeText) {
      meta.appendChild(createEl('span', 'skyview-jumper-type', jumpTypeText));
    }
    if (studentJumpNo) {
      meta.appendChild(createEl('span', 'skyview-jumper-student-no', `#${studentJumpNo}`));
    }
    label.appendChild(meta);
  }

  row.appendChild(label);
  return row;
}

function renderLoadCard(load, showFooterForDate, isNext, state, fadingComment = '', commentIsNew = false, showCrew = true) {
  const card = createEl('div', 'skyview-card');
  if (isNext) {
    card.classList.add('skyview-card--next');
  }

  const header = createEl('div', 'skyview-card-header');
  header.setAttribute('aria-label', 'Liftinformation');

  const jumpers = Array.isArray(load.jumpers) ? load.jumpers : [];
  const rawSeats = String(load.seats || '').trim();
  const match = rawSeats.match(/^(\d+)\s*\/\s*(\d+)$/);
  const seatsDisplay = match ? `${jumpers.length}/${match[2]}` : String(jumpers.length);

  const fields = [
    ['Lift', load.lift || ''],
    ['Platser', seatsDisplay],
    ['Liftchef', load.chief || '–'],
    ['Avgang', load.time || '–'],
  ];

  fields.forEach(([label, value]) => {
    const field = createEl('div', 'skyview-field');
    field.appendChild(createEl('span', 'skyview-label', label));
    field.appendChild(createEl('span', 'skyview-text', value));
    header.appendChild(field);
  });

  card.appendChild(header);

  const effectiveComment = load.comment || fadingComment;
  if (effectiveComment) {
    const isFadingOut = Boolean(fadingComment && !load.comment);
    const cls = isFadingOut
      ? 'skyview-card-comment skyview-card-comment--fading-out'
      : commentIsNew
        ? 'skyview-card-comment skyview-card-comment--new'
        : 'skyview-card-comment';
    const comment = createEl('div', cls);
    comment.appendChild(createEl('span', 'skyview-text', effectiveComment));
    card.appendChild(comment);
  }

  const jumpersWrap = createEl('div', 'skyview-jumpers');
  const segments = buildRenderSegments(jumpers);
  segments.sort((a, b) => {
    const altDiff = segmentAltitude(a) - segmentAltitude(b);
    if (altDiff !== 0) return altDiff;
    if (a.type === 'single' && b.type !== 'single') return -1;
    if (a.type !== 'single' && b.type === 'single') return 1;
    return 0;
  });
  const groupColorMap = buildGroupColorMap(jumpers);

  let prevSegAlt = null;
  segments.forEach((segment) => {
    const curAlt = segmentAltitude(segment);
    const altText = segmentAltitudeText(segment);
    const altBreak = prevSegAlt !== null && curAlt !== prevSegAlt;
    const isFirstAlt = prevSegAlt === null;
    prevSegAlt = curAlt;

    if (altText !== '' && (isFirstAlt || altBreak)) {
      const labelCls = altBreak ? 'skyview-altitude-label skyview-altitude-break' : 'skyview-altitude-label';
      const altLabel = createEl('div', labelCls);
      const firstJumper = segment.type === 'single' ? segment.jumper : (segment.members[0] || {});
      const altUnit = String(firstJumper.altitudeUnit || '').trim().toLowerCase();
      const altDisplay = altUnit ? altText + altUnit : altText;
      altLabel.appendChild(createEl('span', 'skyview-altitude-label-text', altDisplay));
      jumpersWrap.appendChild(altLabel);
    }

    if (segment.type === 'single') {
      const isChief = load.chief && segment.jumper.label.trim().toLowerCase() === load.chief.trim().toLowerCase();
      const row = renderJumperRow(segment.jumper, 0, state.compactView, state.showQuotedNameParts, isChief);
      jumpersWrap.appendChild(row);
      return;
    }

    const hasGroupSourceNo = segment.members.some((jumper) => {
      const value = String(jumper?.jumper_from_group_no || '').trim();
      return value !== '' && value.toLowerCase() !== 'null';
    });

    const color = groupColorMap[segment.groupId] || 0;
    const groupContainer = createEl('div', `skyview-group-container skyview-group-container--color-${color}`);

    if (hasGroupSourceNo) {
      const title = createEl('div', `skyview-group-title skyview-group-title--color-${color}`);
      title.appendChild(createEl('span', 'skyview-group-title-text', segment.groupTitle));

      groupContainer.appendChild(title);
    }

    segment.members.forEach((jumper) => {
      const isChief = load.chief && jumper.label.trim().toLowerCase() === load.chief.trim().toLowerCase();
      groupContainer.appendChild(renderJumperRow(jumper, color, state.compactView, state.showQuotedNameParts, isChief));
    });

    jumpersWrap.appendChild(groupContainer);
  });

  if (segments.length === 0) {
    jumpersWrap.appendChild(createEl('div', 'skyview-empty-drop', 'Inga hoppare'));
  }

  card.appendChild(jumpersWrap);

  const hideMinutesForStatus = Number(load.loadStatus) === 5;
  const hasTimeLeftText = String(load.timeLeftText || '').trim() !== '';
  const hasTimeLeftMinutes =
    load.minutesUntil !== null &&
    load.minutesUntil !== undefined &&
    String(load.minutesUntil).trim() !== '';
  const showTimeLeftFooter = showFooterForDate && !hideMinutesForStatus && (hasTimeLeftText || hasTimeLeftMinutes);

  const loadPilot = formatDisplayName(String(load.pilot || '').trim(), state.showQuotedNameParts);
  const loadJumpLeader = formatDisplayName(String(load.jumpLeader || '').trim(), state.showQuotedNameParts);

  if (showTimeLeftFooter) {
    const footer = createEl('div', 'skyview-card-footer');
    const rawDisplay = load.timeLeftText
      ? String(load.timeLeftText).replace(/(\d+)\s+min/i, '$1min')
      : `${load.minutesUntil}min`;
    footer.appendChild(
      createEl(
        'span',
        `skyview-time-left${urgencyClass(load.minutesUntil)}`,
        rawDisplay
      )
    );
    card.appendChild(footer);
  } else if (!showFooterForDate) {
    const shortTime = (v) => {
      if (!v) return '';
      const s = String(v).trim();
      // ISO datetime: extract HH:MM
      const iso = s.match(/T(\d{2}:\d{2})/);
      if (iso) return iso[1];
      // Already a time like "14:30" or "14:30:00"
      const t = s.match(/^(\d{2}:\d{2})/);
      if (t) return t[1];
      return s;
    };

    const infoItems = [
      ['Lyft', load.liftTime || load.time],
      ['Status', load.loadStatusName],
      ['Hoppade', load.onlyFlying ? 'Nej' : shortTime(load.droppedAt)],
      ['Landade', shortTime(load.landedAt)],
    ];

    const footer = createEl('div', 'skyview-card-footer');
    const infoRow = createEl('div', 'skyview-lift-info');

    infoItems.forEach(([label, value]) => {
      const displayValue = value && String(value).trim() !== '' ? String(value).trim() : '–';
      const item = createEl('div', 'skyview-lift-info-item');
      item.appendChild(createEl('span', 'skyview-lift-info-label', label));
      item.appendChild(createEl('span', 'skyview-lift-info-value', displayValue));
      infoRow.appendChild(item);
    });

    footer.appendChild(infoRow);
    card.appendChild(footer);
  }

  if (showCrew && (loadPilot || loadJumpLeader)) {
    const crewRow = createEl('div', 'skyview-card-crew');
    if (loadPilot) crewRow.appendChild(createEl('span', 'skyview-card-crew-item', `Pilot: ${loadPilot}`));
    if (loadJumpLeader) crewRow.appendChild(createEl('span', 'skyview-card-crew-item', `HL: ${loadJumpLeader}`));
    card.appendChild(crewRow);
  }

  return card;
}

let _swRegistration = null;

function registerSkyviewSW(swUrl) {
  if (!('serviceWorker' in navigator) || !swUrl) return Promise.resolve(null);
  return navigator.serviceWorker.register(swUrl, { scope: '/' })
    .then((reg) => {
      _swRegistration = reg;
      return reg;
    })
    .catch(() => null);
}

function getSWRegistration() {
  if (_swRegistration) return Promise.resolve(_swRegistration);
  if (!('serviceWorker' in navigator)) return Promise.resolve(null);
  return navigator.serviceWorker.ready
    .then((reg) => { _swRegistration = reg; return reg; })
    .catch(() => null);
}

function showPushNotification(title, opts) {
  if (Notification.permission !== 'granted') return;
  getSWRegistration().then((reg) => {
    if (reg && reg.active) {
      reg.showNotification(title, opts);
    } else {
      try { new Notification(title, opts); } catch (_) { /* ignore */ }
    }
  });
}

function urlBase64ToUint8Array(base64String) {
  var padding = '='.repeat((4 - base64String.length % 4) % 4);
  var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  var raw = atob(base64);
  var arr = new Uint8Array(raw.length);
  for (var i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
  return arr;
}

function syncPushSubscription(vapidKey, pushApiBase, state) {
  if (!vapidKey || !pushApiBase) return Promise.resolve();
  var wantsAny = state.notifyNewLoad || state.notifyNewJumper || state.notifyNewMessage || state.notifyNewQueueJumper;

  return getSWRegistration().then(function (reg) {
    if (!reg || !reg.pushManager) return;

    return reg.pushManager.getSubscription().then(function (existing) {
      if (!wantsAny) {
        // Unsubscribe if nothing wanted.
        if (existing) {
          fetch(pushApiBase + '/unsubscribe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ endpoint: existing.endpoint }),
          }).catch(function () {});
          return existing.unsubscribe().catch(function () {});
        }
        return;
      }

      // Subscribe or update.
      var subPromise = existing
        ? Promise.resolve(existing)
        : reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidKey),
          });

      return subPromise.then(function (sub) {
        var subJSON = sub.toJSON();
        return fetch(pushApiBase + '/subscribe', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            subscription: {
              endpoint: subJSON.endpoint,
              keys: subJSON.keys,
            },
            types: {
              newLoad: state.notifyNewLoad,
              newJumper: state.notifyNewJumper,
              newMessage: state.notifyNewMessage,
              newQueueJumper: state.notifyNewQueueJumper,
            },
          }),
        }).catch(function () {});
      });
    });
  }).catch(function () {});
}

function mountSkyview(root) {
  const endpoint = root.dataset.skyviewEndpoint || '';
  const title = root.dataset.skyviewTitle || '';
  const initialDate = root.dataset.skyviewDate || '';
  const refreshSeconds = Number(root.dataset.skyviewRefresh || 0);
  const isLoggedIn = root.dataset.skyviewLoggedIn === '1';
  const swUrl = root.dataset.skyviewSw || '';
  const vapidPublicKey = root.dataset.skyviewVapid || '';
  const pushEndpoint = root.dataset.skyviewPushEndpoint || '';
  const loginUrl = root.dataset.skyviewLoginUrl || '';
  const logoutUrl = root.dataset.skyviewLogoutUrl || '';
  const queueEndpoint = root.dataset.skyviewQueueEndpoint || '';

  if (swUrl) registerSkyviewSW(swUrl);

  const SETTINGS_KEY = 'skyview_settings';

  function loadSettings() {
    try {
      const raw = localStorage.getItem(SETTINGS_KEY);
      if (raw) return JSON.parse(raw);
    } catch (_) { /* ignore */ }
    return {};
  }

  function saveSettings() {
    try {
      localStorage.setItem(SETTINGS_KEY, JSON.stringify({
        autoRefreshEnabled: state.autoRefreshEnabled,
        refreshIntervalSeconds: state.refreshIntervalSeconds,
        showQuotedNameParts: state.showQuotedNameParts,
        theme: state.theme,
        compactView: state.compactView,
        notifyNewLoad: state.notifyNewLoad,
        notifyNewJumper: state.notifyNewJumper,
        notifyNewMessage: state.notifyNewMessage,
        soundEnabled: state.soundEnabled,
        soundNewJumper: state.soundNewJumper,
        notifyNewQueueJumper: state.notifyNewQueueJumper,
        soundNewQueueJumper: state.soundNewQueueJumper,
        maxLoads: state.maxLoads,
      }));
    } catch (_) { /* ignore */ }
  }

  const saved = loadSettings();

  const state = {
    endpoint,
    title,
    selectedDate: initialDate,
    autoRefreshEnabled: typeof saved.autoRefreshEnabled === 'boolean' ? saved.autoRefreshEnabled : refreshSeconds > 0,
    refreshIntervalSeconds: Number.isFinite(saved.refreshIntervalSeconds) && saved.refreshIntervalSeconds > 0 ? saved.refreshIntervalSeconds : (refreshSeconds > 0 ? refreshSeconds : 30),
    isLoggedIn,
    loads: [],
    jumpQueueCount: null,
    loading: true,
    error: '',
    message: '',
    clock: new Date(),
    knownLoadIds: new Set(),
    newLoadIds: new Set(),
    firstRender: true,
    refreshTimer: null,
    clockTimer: null,
    calendarOpen: false,
    settingsOpen: false,
    queueModalOpen: false,
    queueList: [],
    queueLoading: false,
    showQuotedNameParts: typeof saved.showQuotedNameParts === 'boolean' ? saved.showQuotedNameParts : true,
    theme: ['dark', 'light', 'midnight', 'sunset', 'forest', 'arctic', 'contrast', 'ocean', 'lavender', 'cherry', 'neon-pink', 'neon-green', 'neon-blue', 'aros', 'skydiver', 'airport', 'classic'].includes(saved.theme) ? saved.theme : 'classic',
    compactView: typeof saved.compactView === 'boolean' ? saved.compactView : false,
    notifyNewLoad: typeof saved.notifyNewLoad === 'boolean' ? saved.notifyNewLoad : (typeof saved.notificationsEnabled === 'boolean' ? saved.notificationsEnabled : false),
    notifyNewJumper: typeof saved.notifyNewJumper === 'boolean' ? saved.notifyNewJumper : (typeof saved.notifyWatchedJumper === 'boolean' ? saved.notifyWatchedJumper : false),
    notifyNewMessage: typeof saved.notifyNewMessage === 'boolean' ? saved.notifyNewMessage : false,
    soundEnabled: typeof saved.soundEnabled === 'boolean' ? saved.soundEnabled : false,
    soundNewJumper: typeof saved.soundNewJumper === 'boolean' ? saved.soundNewJumper : false,
    notifyNewQueueJumper: typeof saved.notifyNewQueueJumper === 'boolean' ? saved.notifyNewQueueJumper : false,
    soundNewQueueJumper: typeof saved.soundNewQueueJumper === 'boolean' ? saved.soundNewQueueJumper : false,
    maxLoads: Number.isFinite(saved.maxLoads) && saved.maxLoads >= 0 ? saved.maxLoads : 0,
    offlineMode: false,
    knownJumperCounts: {},
    knownQueueBookingIds: new Set(),
    prevJumpQueueCount: null,
    knownMessage: '',
    hasFetchedOnce: false,
    altitudeUnit: '',
    calendarMonth: startOfMonth(parseDateString(initialDate) || new Date()),
    prevComments: {},
    fadingOutComments: new Map(),
    prevMessage: '',
    fadingOutMessage: '',
  };

  function buildUrl() {
    if (!state.endpoint) return '';

    const url = new URL(state.endpoint, window.location.origin);
    if (state.selectedDate) {
      url.searchParams.set('date', state.selectedDate);
    } else {
      url.searchParams.delete('date');
    }

    return url.toString();
  }

  function mergeLoads(prev, next) {
    const prevIds = prev.map((l) => l.id);
    const nextMap = Object.fromEntries(next.map((l) => [l.id, l]));
    const nextIds = next.map((l) => l.id);

    const merged = prevIds
      .filter((id) => nextMap[id])
      .map((id) => nextMap[id]);

    nextIds.forEach((id) => {
      if (!prevIds.includes(id)) merged.push(nextMap[id]);
    });

    return merged.sort((a, b) => {
      const left = Number.isFinite(Number(a.loadNo)) ? Number(a.loadNo) : Number.MAX_SAFE_INTEGER;
      const right = Number.isFinite(Number(b.loadNo)) ? Number(b.loadNo) : Number.MAX_SAFE_INTEGER;

      if (left === right) {
        return String(a.id).localeCompare(String(b.id));
      }

      return left - right;
    });
  }

  function animateLoadRemovalTransition(removedLoadIds, nextOrderIds) {
    return new Promise((resolve) => {
      const board = root.querySelector('.skyview-board');
      if (!board) {
        resolve();
        return;
      }

      const wraps = Array.from(board.querySelectorAll('.skyview-load-sortable'));
      if (wraps.length === 0) {
        resolve();
        return;
      }

      const wrapById = new Map();
      wraps.forEach((wrap) => {
        const id = String(wrap.dataset.loadId || '');
        if (id !== '') {
          wrapById.set(id, wrap);
        }
      });

      const removedWraps = removedLoadIds
        .map((id) => wrapById.get(String(id)))
        .filter(Boolean);

      if (removedWraps.length === 0) {
        resolve();
        return;
      }

      const removedSet = new Set(removedWraps);
      const remainingWraps = wraps.filter((wrap) => !removedSet.has(wrap));
      const firstRects = new Map();
      remainingWraps.forEach((wrap) => {
        firstRects.set(wrap, wrap.getBoundingClientRect());
      });

      removedWraps.forEach((wrap) => {
        wrap.classList.add('skyview-load-sortable--removing');
      });

      window.setTimeout(() => {
        removedWraps.forEach((wrap) => {
          if (wrap.parentNode === board) {
            board.removeChild(wrap);
          }
        });

        const orderedRemaining = nextOrderIds
          .map((id) => wrapById.get(String(id)))
          .filter((wrap) => wrap && wrap.parentNode === board);

        orderedRemaining.forEach((wrap) => board.appendChild(wrap));

        let maxStaggerDelay = 0;
        let movedCount = 0;
        const movedCards = [];
        orderedRemaining.forEach((wrap) => {
          const first = firstRects.get(wrap);
          const last = wrap.getBoundingClientRect();
          if (!first || !last) {
            return;
          }

          const dx = first.left - last.left;
          const dy = first.top - last.top;
          if (Math.abs(dx) < 0.5 && Math.abs(dy) < 0.5) {
            return;
          }

          // Start each moved card a bit later for a clearly sequential effect.
          const staggerDelay = Math.min(movedCount * 90, 720);
          movedCount += 1;
          maxStaggerDelay = Math.max(maxStaggerDelay, staggerDelay);

          wrap.classList.add('skyview-load-sortable--reflow');
          wrap.style.transition = 'none';
          wrap.style.transform = `translate(${dx}px, ${dy}px)`;
          wrap.getBoundingClientRect();
          movedCards.push({ wrap, staggerDelay });
        });

        movedCards.forEach(({ wrap, staggerDelay }) => {
          window.setTimeout(() => {
            wrap.style.transition = '';
            wrap.style.transitionDelay = '0ms';
            wrap.style.transform = '';
          }, staggerDelay);
        });

        window.setTimeout(() => {
          orderedRemaining.forEach((wrap) => {
            wrap.classList.remove('skyview-load-sortable--reflow');
            wrap.style.transitionDelay = '';
          });
          resolve();
        }, 620 + maxStaggerDelay);
      }, 320);
    });
  }

  function applyPendingInsertTransition(board) {
    const pending = state.pendingInsertTransition;
    if (!pending || !board) {
      return;
    }

    state.pendingInsertTransition = null;

    const wrapById = new Map();
    Array.from(board.querySelectorAll('.skyview-load-sortable')).forEach((wrap) => {
      const id = String(wrap.dataset.loadId || '');
      if (id !== '') {
        wrapById.set(id, wrap);
      }
    });

    const movedCards = [];
    pending.firstRects.forEach((first, id) => {
      const wrap = wrapById.get(id);
      if (!wrap) {
        return;
      }

      const last = wrap.getBoundingClientRect();
      const dx = first.left - last.left;
      const dy = first.top - last.top;
      if (Math.abs(dx) < 0.5 && Math.abs(dy) < 0.5) {
        return;
      }

      wrap.classList.add('skyview-load-sortable--reflow');
      wrap.style.transition = 'none';
      wrap.style.transform = `translate(${dx}px, ${dy}px)`;
      wrap.getBoundingClientRect();
      movedCards.push(wrap);
    });

    if (movedCards.length === 0) {
      return;
    }

    let maxStaggerDelay = 0;
    movedCards.forEach((wrap, index) => {
      const staggerDelay = Math.min(index * 90, 720);
      maxStaggerDelay = Math.max(maxStaggerDelay, staggerDelay);

      window.setTimeout(() => {
        wrap.style.transition = '';
        wrap.style.transitionDelay = '0ms';
        wrap.style.transform = '';
      }, staggerDelay);
    });

    window.setTimeout(() => {
      movedCards.forEach((wrap) => {
        wrap.classList.remove('skyview-load-sortable--reflow');
        wrap.style.transitionDelay = '';
      });
    }, 620 + maxStaggerDelay);
  }

  async function fetchLoads() {
    try {
      const res = await fetch(buildUrl(), {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      const data = await res.json();

      if (!res.ok) {
        state.message = '';
        state.error = `HTTP ${res.status}: ${data?.message || data?.error || res.statusText}`;
      } else if (data.error) {
        state.message = '';
        state.error = data.error;
      } else {
        state.error = '';
        const incomingMessage = String(data?.message || '').trim();
        if (state.notifyNewMessage && state.hasFetchedOnce && incomingMessage && incomingMessage !== state.knownMessage) {
          showPushNotification('Nytt meddelande', { body: incomingMessage, icon: '/favicon.ico' });
        }
        if (incomingMessage !== state.knownMessage) state.knownMessage = incomingMessage;
        state.message = incomingMessage;
        state.altitudeUnit = String(data?.altitudeUnit || '').trim();
        const prevQueueCount = state.jumpQueueCount;
        state.jumpQueueCount =
          data?.jumpQueueCount !== null &&
          data?.jumpQueueCount !== undefined &&
          String(data.jumpQueueCount).trim() !== '' &&
          Number.isFinite(Number(data.jumpQueueCount))
            ? Number(data.jumpQueueCount)
            : null;
        if (state.hasFetchedOnce && prevQueueCount !== null && state.jumpQueueCount !== null && state.jumpQueueCount > prevQueueCount) {
          if (state.notifyNewQueueJumper) {
            showPushNotification('Ny i önskelistan', { body: `${state.jumpQueueCount} i kön`, icon: '/favicon.ico' });
          }
          if (state.soundNewQueueJumper) playPingSound();
        }
        const previousLoads = state.loads;
        const previousIds = new Set(previousLoads.map((load) => load.id));
        const merged = mergeLoads(previousLoads, data.loads || []);
        const freshIds = new Set(merged.map((l) => l.id));
        const removedLoadIds = previousLoads
          .map((load) => load.id)
          .filter((id) => !freshIds.has(id));
        const addedLoadIds = merged
          .map((load) => load.id)
          .filter((id) => !previousIds.has(id));

        if (removedLoadIds.length > 0 && !state.firstRender) {
          await animateLoadRemovalTransition(removedLoadIds, merged.map((load) => load.id));
        }

        if (removedLoadIds.length === 0 && addedLoadIds.length > 0 && !state.firstRender) {
          const board = root.querySelector('.skyview-board');
          if (board) {
            const firstRects = new Map();
            Array.from(board.querySelectorAll('.skyview-load-sortable')).forEach((wrap) => {
              const id = String(wrap.dataset.loadId || '');
              if (id !== '') {
                firstRects.set(id, wrap.getBoundingClientRect());
              }
            });
            state.pendingInsertTransition = { firstRects };
          }
        }

        state.newLoadIds = new Set();
        freshIds.forEach((id) => {
          if (!state.knownLoadIds.has(id)) state.newLoadIds.add(id);
        });
        let soundPlayed = false;
        if (state.hasFetchedOnce && addedLoadIds.length > 0) {
          if (state.notifyNewLoad) {
            const total = merged.length;
            const msg = addedLoadIds.length === 1
              ? 'Lift nummer ' + total + ' tillagd!'
              : addedLoadIds.length + ' nya liftar tillagda!';
            showPushNotification('SkyView', { body: msg, tag: 'skyview-newLoad', icon: '/favicon.ico' });
          }
          if (state.soundEnabled) { playPingSound(); soundPlayed = true; }
        }

        {
          const currentCounts = {};
          const keyToLabel = {};
          const keyToLoadNum = {};
          merged.forEach((load, loadIdx) => {
            (load.jumpers || []).forEach((j) => {
              const label = (j.label || '').trim();
              const key = (j.internalNo || '').trim() || label.toLowerCase();
              if (key) {
                currentCounts[key] = (currentCounts[key] || 0) + 1;
                if (label && !keyToLabel[key]) keyToLabel[key] = label;
                keyToLoadNum[key] = loadIdx + 1;
              }
            });
          });
          if (state.hasFetchedOnce) {
            let newJumperLoadNums = [];
            for (const key in currentCounts) {
              if (currentCounts[key] > (state.knownJumperCounts[key] || 0)) {
                newJumperLoadNums.push(keyToLoadNum[key]);
              }
            }
            if (newJumperLoadNums.length > 0) {
              if (state.notifyNewJumper) {
                const msg = newJumperLoadNums.length === 1
                  ? 'Ny hoppare lades till i lift nr ' + newJumperLoadNums[0]
                  : newJumperLoadNums.length + ' nya hoppare lades till i lift nr ' + [...new Set(newJumperLoadNums)].join(', ');
                showPushNotification('SkyView', { body: msg, tag: 'skyview-newJumper', icon: '/favicon.ico' });
              }
              if (state.soundNewJumper && !soundPlayed) playPingSound();
            }
          }
          state.knownJumperCounts = currentCounts;
        }

        state.knownLoadIds = freshIds;
        state.hasFetchedOnce = true;
        state.loads = merged;
        state.offlineMode = false;
        try { localStorage.setItem(SETTINGS_KEY + '_cache', JSON.stringify(data)); } catch (_) { /* ignore */ }
      }
    } catch (err) {
      try {
        const cached = localStorage.getItem(SETTINGS_KEY + '_cache');
        if (cached) {
          const cacheData = JSON.parse(cached);
          state.error = '';
          state.message = String(cacheData?.message || '').trim();
          state.loads = cacheData.loads || [];
          state.offlineMode = true;
        } else {
          state.message = '';
          state.error = 'N\u00e4tverksfel: ' + err.message;
        }
      } catch (_) {
        state.message = '';
        state.error = 'N\u00e4tverksfel: ' + err.message;
      }
    } finally {
      state.loading = false;
      // Skip re-render while settings panel or queue modal is open so focused
      // inputs/dropdowns don't lose focus and modals don't flash.
      if (!state.settingsOpen && !state.queueModalOpen) {
        render();
      }
    }
  }

  async function fetchJumpQueue() {
    if (!queueEndpoint) return;
    state.queueLoading = true;
    // Only show loading state on first load (no existing data); avoids flashing
    // content away when refreshing while the modal is already open.
    if (state.queueList.length === 0) render();
    try {
      const url = new URL(queueEndpoint, window.location.origin);
      if (state.selectedDate) url.searchParams.set('date', state.selectedDate);
      const res = await fetch(url.toString(), {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      const data = await res.json();
      const incoming = Array.isArray(data?.items) ? data.items : [];
      state.knownQueueBookingIds = new Set(incoming.map((item) => item.bookingId).filter(Boolean));
      state.queueList = incoming;
    } catch (_) {
      state.queueList = [];
    }
    state.queueLoading = false;
    if (state.queueModalOpen) {
      const existingModal = root.querySelector('.skyview-queue-modal');
      if (existingModal) {
        buildQueueModalBody(existingModal);
        return;
      }
    }
    render();
  }

  function scheduleRefresh() {
    if (state.refreshTimer) {
      clearInterval(state.refreshTimer);
      state.refreshTimer = null;
    }

    const shouldAutoRefresh =
      state.autoRefreshEnabled &&
      state.refreshIntervalSeconds > 0 &&
      (state.selectedDate === '' || state.selectedDate === todayDate());

    if (shouldAutoRefresh) {
      state.refreshTimer = setInterval(fetchLoads, state.refreshIntervalSeconds * 1000);
    }
  }

  function renderCalendarPopup() {
    const popup = createEl('div', 'skyview-datepicker-popup');

    const header = createEl('div', 'skyview-datepicker-header');
    const prevBtn = createEl('button', 'skyview-datepicker-nav skyview-datepicker-nav--prev', '‹');
    prevBtn.type = 'button';
    prevBtn.setAttribute('aria-label', 'Föregående månad');
    prevBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      state.calendarMonth = addMonths(state.calendarMonth, -1);
      render();
    });

    const nextBtn = createEl('button', 'skyview-datepicker-nav skyview-datepicker-nav--next', '›');
    nextBtn.type = 'button';
    nextBtn.setAttribute('aria-label', 'Nästa månad');
    nextBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      state.calendarMonth = addMonths(state.calendarMonth, 1);
      render();
    });

    const monthLabel = createEl(
      'div',
      'skyview-datepicker-month',
      state.calendarMonth.toLocaleDateString('sv-SE', { month: 'long', year: 'numeric' })
    );

    header.appendChild(prevBtn);
    header.appendChild(monthLabel);
    header.appendChild(nextBtn);
    popup.appendChild(header);

    const weekdays = createEl('div', 'skyview-datepicker-weekdays');
    ['Mån', 'Tis', 'Ons', 'Tor', 'Fre', 'Lör', 'Sön'].forEach((name, index) => {
      const weekday = createEl('span', 'skyview-datepicker-weekday', name);
      if (index === 6) {
        weekday.classList.add('skyview-datepicker-weekday--sunday');
      }
      weekdays.appendChild(weekday);
    });
    popup.appendChild(weekdays);

    const grid = createEl('div', 'skyview-datepicker-grid');
    const firstOfMonth = startOfMonth(state.calendarMonth);
    const dayOffset = (firstOfMonth.getDay() + 6) % 7;
    const gridStart = new Date(firstOfMonth.getFullYear(), firstOfMonth.getMonth(), 1 - dayOffset);
    const selected = state.selectedDate;
    const today = todayDate();

    for (let i = 0; i < 42; i += 1) {
      const date = new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + i);
      const dayBtn = createEl('button', 'skyview-datepicker-day', String(date.getDate()));
      dayBtn.type = 'button';

      const dayString = toDateString(date);
      if (date.getMonth() !== state.calendarMonth.getMonth()) {
        dayBtn.classList.add('skyview-datepicker-day--muted');
      }
      if (dayString === selected) {
        dayBtn.classList.add('skyview-datepicker-day--selected');
      }
      if (dayString === today) {
        dayBtn.classList.add('skyview-datepicker-day--today');
      }
      if (date.getDay() === 0) {
        dayBtn.classList.add('skyview-datepicker-day--sunday');
      }

      dayBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        state.selectedDate = dayString;
        state.calendarOpen = false;
        state.calendarMonth = startOfMonth(date);
        state.loading = true;
        scheduleRefresh();
        render();
        fetchLoads();
      });

      grid.appendChild(dayBtn);
    }

    popup.appendChild(grid);
    return popup;
  }

  function renderSettingsPanel() {
    const panel = createEl('div', 'skyview-settings-panel');
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'Inställningar');

    const header = createEl('div', 'skyview-settings-modal-header');
    header.appendChild(createEl('span', 'skyview-settings-title', 'Inställningar'));
    const closeBtn = createEl('button', 'skyview-settings-modal-close', '✕');
    closeBtn.type = 'button';
    closeBtn.setAttribute('aria-label', 'Stäng inställningar');
    closeBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      state.settingsOpen = false;
      render();
    });
    header.appendChild(closeBtn);
    panel.appendChild(header);

    const list = createEl('div', 'skyview-settings-list');

    const toggleItem = createEl('label', 'skyview-settings-item skyview-settings-control');
    const toggleText = createEl('span', 'skyview-settings-label', 'Auto-uppdatering');
    const toggle = createEl('input', 'skyview-settings-toggle');
    toggle.type = 'checkbox';
    toggle.checked = Boolean(state.autoRefreshEnabled);
    toggle.addEventListener('change', () => {
      state.autoRefreshEnabled = toggle.checked;
      saveSettings();
      scheduleRefresh();
      if (state.autoRefreshEnabled && (state.selectedDate === '' || state.selectedDate === todayDate())) {
        state.loading = true;
        render();
        fetchLoads();
        return;
      }
      render();
    });
    toggleItem.appendChild(toggleText);
    toggleItem.appendChild(toggle);
    list.appendChild(toggleItem);

    const intervalItem = createEl('label', 'skyview-settings-item skyview-settings-control');
    const intervalLabel = createEl('span', 'skyview-settings-label', 'Uppdateringsintervall');
    const intervalSelect = createEl('select', 'skyview-settings-select');
    [10, 20, 30, 60, 120].forEach((seconds) => {
      const option = createEl('option', '', `${seconds} sek`);
      option.value = String(seconds);
      if (seconds === Number(state.refreshIntervalSeconds)) {
        option.selected = true;
      }
      intervalSelect.appendChild(option);
    });
    intervalSelect.addEventListener('change', () => {
      const next = Number(intervalSelect.value);
      if (Number.isFinite(next) && next > 0) {
        state.refreshIntervalSeconds = next;
        saveSettings();
        scheduleRefresh();
      }
      render();
    });
    intervalItem.appendChild(intervalLabel);
    intervalItem.appendChild(intervalSelect);
    list.appendChild(intervalItem);

    const quotedNameItem = createEl('label', 'skyview-settings-item skyview-settings-control');
    const quotedNameLabel = createEl('span', 'skyview-settings-label', 'Visa smeknamn');
    const quotedNameToggle = createEl('input', 'skyview-settings-toggle');
    quotedNameToggle.type = 'checkbox';
    quotedNameToggle.checked = Boolean(state.showQuotedNameParts);
    quotedNameToggle.addEventListener('change', () => {
      state.showQuotedNameParts = quotedNameToggle.checked;
      saveSettings();
      render();
    });
    quotedNameItem.appendChild(quotedNameLabel);
    quotedNameItem.appendChild(quotedNameToggle);
    list.appendChild(quotedNameItem);

    const themeItem = createEl('label', 'skyview-settings-item skyview-settings-control');
    const themeLabel = createEl('span', 'skyview-settings-label', 'Tema');
    const themeSelect = createEl('select', 'skyview-settings-select');
    const themes = [
      { value: 'classic', label: 'Classic' },
      { value: 'dark', label: 'Mörkt' },
      { value: 'light', label: 'Ljust' },
      { value: 'midnight', label: 'Midnight' },
      { value: 'sunset', label: 'Sunset' },
      { value: 'forest', label: 'Forest' },
      { value: 'arctic', label: 'Arctic' },
      { value: 'contrast', label: 'Kontrast' },
      { value: 'ocean', label: 'Ocean' },
      { value: 'lavender', label: 'Lavender' },
      { value: 'cherry', label: 'Cherry' },
      { value: 'neon-pink', label: 'Neon Rosa' },
      { value: 'neon-green', label: 'Neon Grön' },
      { value: 'neon-blue', label: 'Neon Blå' },
      { value: 'aros', label: 'Aros' },
      { value: 'skydiver', label: 'Skydiver' },
      { value: 'airport', label: 'Flygplats' },
    ];
    themes.forEach((t) => {
      const option = createEl('option', '', t.label);
      option.value = t.value;
      if (t.value === state.theme) option.selected = true;
      themeSelect.appendChild(option);
    });
    themeSelect.addEventListener('change', () => {
      state.theme = themeSelect.value;
      saveSettings();
      render();
    });
    themeItem.appendChild(themeLabel);
    themeItem.appendChild(themeSelect);
    list.appendChild(themeItem);

    const maxLoadsItem = createEl('label', 'skyview-settings-item skyview-settings-control');
    const maxLoadsLabel = createEl('span', 'skyview-settings-label', 'Max liftar att visa');
    const maxLoadsSelect = createEl('select', 'skyview-settings-select');
    [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10].forEach((n) => {
      const option = createEl('option', '', n === 0 ? 'Alla' : String(n));
      option.value = String(n);
      if (n === Number(state.maxLoads)) option.selected = true;
      maxLoadsSelect.appendChild(option);
    });
    maxLoadsSelect.addEventListener('change', () => {
      state.maxLoads = Number(maxLoadsSelect.value);
      saveSettings();
      render();
    });
    maxLoadsItem.appendChild(maxLoadsLabel);
    maxLoadsItem.appendChild(maxLoadsSelect);
    list.appendChild(maxLoadsItem);

    const compactItem = createEl('label', 'skyview-settings-item skyview-settings-control');
    const compactLabel = createEl('span', 'skyview-settings-label', 'Kompakt vy');
    const compactToggle = createEl('input', 'skyview-settings-toggle');
    compactToggle.type = 'checkbox';
    compactToggle.checked = Boolean(state.compactView);
    compactToggle.addEventListener('change', () => {
      state.compactView = compactToggle.checked;
      saveSettings();
      render();
    });
    compactItem.appendChild(compactLabel);
    compactItem.appendChild(compactToggle);
    list.appendChild(compactItem);

    if (state.isLoggedIn) {
    function ensureNotificationPermission() {
      if (!('Notification' in window)) return;
      if (Notification.permission === 'default') {
        Notification.requestPermission();
      }
    }

    const notifyLoadItem = createEl('label', 'skyview-settings-item skyview-settings-control');
    const notifyLoadLabel = createEl('span', 'skyview-settings-label', 'Notis vid ny lift');
    const notifyLoadToggle = createEl('input', 'skyview-settings-toggle');
    notifyLoadToggle.type = 'checkbox';
    notifyLoadToggle.checked = Boolean(state.notifyNewLoad);
    notifyLoadToggle.addEventListener('change', () => {
      state.notifyNewLoad = notifyLoadToggle.checked;
      if (state.notifyNewLoad) ensureNotificationPermission();
      saveSettings();
      syncPushSubscription(vapidPublicKey, pushEndpoint, state);
    });
    notifyLoadItem.appendChild(notifyLoadLabel);
    notifyLoadItem.appendChild(notifyLoadToggle);
    list.appendChild(notifyLoadItem);

    const notifyJumperItem = createEl('label', 'skyview-settings-item skyview-settings-control');
    const notifyJumperLabel = createEl('span', 'skyview-settings-label', 'Notis för hoppare');
    const notifyJumperToggle = createEl('input', 'skyview-settings-toggle');
    notifyJumperToggle.type = 'checkbox';
    notifyJumperToggle.checked = Boolean(state.notifyNewJumper);
    notifyJumperToggle.addEventListener('change', () => {
      state.notifyNewJumper = notifyJumperToggle.checked;
      if (state.notifyNewJumper) ensureNotificationPermission();
      saveSettings();
      syncPushSubscription(vapidPublicKey, pushEndpoint, state);
    });
    notifyJumperItem.appendChild(notifyJumperLabel);
    notifyJumperItem.appendChild(notifyJumperToggle);
    list.appendChild(notifyJumperItem);

    const notifyMessageItem = createEl('label', 'skyview-settings-item skyview-settings-control');
    const notifyMessageLabel = createEl('span', 'skyview-settings-label', 'Notis vid nytt meddelande');
    const notifyMessageToggle = createEl('input', 'skyview-settings-toggle');
    notifyMessageToggle.type = 'checkbox';
    notifyMessageToggle.checked = Boolean(state.notifyNewMessage);
    notifyMessageToggle.addEventListener('change', () => {
      state.notifyNewMessage = notifyMessageToggle.checked;
      if (state.notifyNewMessage) ensureNotificationPermission();
      saveSettings();
      syncPushSubscription(vapidPublicKey, pushEndpoint, state);
    });
    notifyMessageItem.appendChild(notifyMessageLabel);
    notifyMessageItem.appendChild(notifyMessageToggle);
    list.appendChild(notifyMessageItem);

    const soundItem = createEl('label', 'skyview-settings-item skyview-settings-control');
    const soundLabel = createEl('span', 'skyview-settings-label', 'Ljud vid ny lift');
    const soundToggle = createEl('input', 'skyview-settings-toggle');
    soundToggle.type = 'checkbox';
    soundToggle.checked = Boolean(state.soundEnabled);
    soundToggle.addEventListener('change', () => {
      state.soundEnabled = soundToggle.checked;
      saveSettings();
      if (soundToggle.checked) playPingSound();
    });
    soundItem.appendChild(soundLabel);
    soundItem.appendChild(soundToggle);
    list.appendChild(soundItem);

    const soundJumperItem = createEl('label', 'skyview-settings-item skyview-settings-control');
    const soundJumperLabel = createEl('span', 'skyview-settings-label', 'Ljud för ny hoppare');
    const soundJumperToggle = createEl('input', 'skyview-settings-toggle');
    soundJumperToggle.type = 'checkbox';
    soundJumperToggle.checked = Boolean(state.soundNewJumper);
    soundJumperToggle.addEventListener('change', () => {
      state.soundNewJumper = soundJumperToggle.checked;
      saveSettings();
      if (soundJumperToggle.checked) playPingSound();
    });
    soundJumperItem.appendChild(soundJumperLabel);
    soundJumperItem.appendChild(soundJumperToggle);
    list.appendChild(soundJumperItem);

    const notifyQueueItem = createEl('label', 'skyview-settings-item skyview-settings-control');
    const notifyQueueLabel = createEl('span', 'skyview-settings-label', 'Notis ny i önskelistan');
    const notifyQueueToggle = createEl('input', 'skyview-settings-toggle');
    notifyQueueToggle.type = 'checkbox';
    notifyQueueToggle.checked = Boolean(state.notifyNewQueueJumper);
    notifyQueueToggle.addEventListener('change', () => {
      state.notifyNewQueueJumper = notifyQueueToggle.checked;
      if (state.notifyNewQueueJumper) ensureNotificationPermission();
      saveSettings();
      syncPushSubscription(vapidPublicKey, pushEndpoint, state);
    });
    notifyQueueItem.appendChild(notifyQueueLabel);
    notifyQueueItem.appendChild(notifyQueueToggle);
    list.appendChild(notifyQueueItem);

    const soundQueueItem = createEl('label', 'skyview-settings-item skyview-settings-control');
    const soundQueueLabel = createEl('span', 'skyview-settings-label', 'Ljud ny i önskelistan');
    const soundQueueToggle = createEl('input', 'skyview-settings-toggle');
    soundQueueToggle.type = 'checkbox';
    soundQueueToggle.checked = Boolean(state.soundNewQueueJumper);
    soundQueueToggle.addEventListener('change', () => {
      state.soundNewQueueJumper = soundQueueToggle.checked;
      saveSettings();
      if (soundQueueToggle.checked) playPingSound();
    });
    soundQueueItem.appendChild(soundQueueLabel);
    soundQueueItem.appendChild(soundQueueToggle);
    list.appendChild(soundQueueItem);
    } // end isLoggedIn

    if (!state.isLoggedIn && loginUrl) {
      const loginItem = createEl('div', 'skyview-settings-item skyview-settings-login');
      const loginBtn = createEl('a', 'skyview-login-button', 'Logga in');
      loginBtn.href = loginUrl;
      loginItem.appendChild(loginBtn);
      list.appendChild(loginItem);
    }

    if (state.isLoggedIn && logoutUrl) {
      const logoutItem = createEl('div', 'skyview-settings-item skyview-settings-login');
      const logoutBtn = createEl('a', 'skyview-login-button skyview-logout-button', 'Logga ut');
      logoutBtn.href = logoutUrl;
      logoutItem.appendChild(logoutBtn);
      list.appendChild(logoutItem);
    }

    panel.appendChild(list);

    return panel;
  }

  function buildQueueModalBody(modal) {
    while (modal.children.length > 1) {
      modal.removeChild(modal.lastChild);
    }
    if (state.queueLoading) {
      modal.appendChild(createEl('div', 'skyview-queue-modal-loading', 'Laddar...'));
    } else if (state.queueList.length === 0) {
      modal.appendChild(createEl('div', 'skyview-queue-modal-empty', 'Kön är tom.'));
    } else {
      const jumpersWrap = createEl('div', 'skyview-jumpers skyview-queue-jumpers');
      const segments = buildRenderSegments(state.queueList);
      const groupColorMap = buildGroupColorMap(state.queueList);

      segments.forEach((segment) => {
        if (segment.type === 'single') {
          jumpersWrap.appendChild(renderJumperRow(segment.jumper, 0, false, state.showQuotedNameParts, false, true));
          return;
        }

        const color = groupColorMap[segment.groupId] || 0;
        const groupContainer = createEl('div', `skyview-group-container skyview-group-container--color-${color}`);

        segment.members.forEach((jumper) => {
          groupContainer.appendChild(renderJumperRow(jumper, color, false, state.showQuotedNameParts, false, true));
        });

        jumpersWrap.appendChild(groupContainer);
      });

      modal.appendChild(jumpersWrap);

      const footer = createEl('div', 'skyview-queue-modal-footer');
      footer.appendChild(createEl('span', 'skyview-queue-modal-count', `${state.queueList.length} i kön`));
      modal.appendChild(footer);
    }
  }

  function render() {
    root.innerHTML = '';

    function renderOverlays() {
      const existingQueueModal = root.querySelector('.skyview-queue-modal-overlay');
      if (existingQueueModal) existingQueueModal.remove();

      const existingSettingsModal = root.querySelector('.skyview-settings-modal-overlay');
      if (existingSettingsModal) existingSettingsModal.remove();

      if (state.settingsOpen) {
        const settingsOverlay = createEl('div', 'skyview-settings-modal-overlay');
        const settingsPanel = renderSettingsPanel();
        settingsOverlay.appendChild(settingsPanel);
        settingsOverlay.addEventListener('click', (e) => {
          if (e.target === settingsOverlay) {
            state.settingsOpen = false;
            render();
          }
        });
        root.appendChild(settingsOverlay);
      }

      if (state.queueModalOpen) {
        const overlay = createEl('div', 'skyview-queue-modal-overlay');

        const modal = createEl('div', 'skyview-queue-modal');

        const header = createEl('div', 'skyview-queue-modal-header');
        header.appendChild(createEl('span', 'skyview-queue-modal-title', 'Kö'));
        const closeBtn = createEl('button', 'skyview-queue-modal-close', '✕');
        closeBtn.type = 'button';
        closeBtn.setAttribute('aria-label', 'Stäng');
        closeBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          state.queueModalOpen = false;
          render();
        });
        header.appendChild(closeBtn);
        modal.appendChild(header);

        buildQueueModalBody(modal);

        overlay.appendChild(modal);
        overlay.addEventListener('click', (e) => {
          if (e.target === overlay) {
            state.queueModalOpen = false;
            render();
          }
        });
        root.appendChild(overlay);
      }
    }

    const allThemes = ['light', 'midnight', 'sunset', 'forest', 'arctic', 'contrast', 'ocean', 'lavender', 'cherry', 'neon-pink', 'neon-green', 'neon-blue', 'aros', 'skydiver', 'airport', 'classic'];
    allThemes.forEach((t) => root.classList.remove('skyview-page--' + t));
    if (state.theme !== 'dark') root.classList.add('skyview-page--' + state.theme);
    root.classList.toggle('skyview-page--compact', state.compactView);
    const themeBg = { dark: '#0d1b2a', light: '#f0f4f8', midnight: '#000000', sunset: '#1a0f0a', forest: '#0a1a10', arctic: '#eaf2f8', contrast: '#000000', ocean: '#031525', lavender: '#f0edf6', cherry: '#1a0a0e', 'neon-pink': '#000000', 'neon-green': '#000000', 'neon-blue': '#000000', aros: '#07162a', skydiver: '#010810', airport: '#050505', classic: '#ffffff' };
    document.body.style.backgroundColor = themeBg[state.theme] || '';
    document.body.style.overflow = (state.settingsOpen || state.queueModalOpen) ? 'hidden' : '';

    const toolbar = createEl('div', 'skyview-toolbar');
    const toolbarLeft = createEl('div', 'skyview-toolbar-left');
    const toolbarRight = createEl('div', 'skyview-toolbar-right');

    if (state.title.trim() !== '') {
      toolbarLeft.appendChild(createEl('h2', 'skyview-title', state.title));
    }

    const settingsWrap = createEl('div', 'skyview-settings-wrapper');
    const settingsButton = createEl(
      'button',
      `skyview-settings-button${state.settingsOpen ? ' skyview-settings-button--active' : ''}`
    );
    settingsButton.type = 'button';
    settingsButton.title = 'Inställningar';
    settingsButton.setAttribute('aria-label', 'Visa inställningar');
    settingsButton.appendChild(createSettingsIcon());
    settingsButton.addEventListener('click', (e) => {
      e.stopPropagation();
      state.settingsOpen = !state.settingsOpen;
      if (state.settingsOpen) state.calendarOpen = false;
      render();
    });
    settingsWrap.appendChild(settingsButton);

    toolbarLeft.appendChild(settingsWrap);

    if (state.isLoggedIn) {
      const dateControl = createEl('label', 'skyview-date-control');
      const dateActions = createEl('div', 'skyview-date-actions');
      const dateButton = createEl(
        'button',
        'skyview-date-button'
      );
      dateButton.appendChild(createCalendarIcon());
      if (state.selectedDate) {
        dateButton.appendChild(document.createTextNode(' ' + state.selectedDate));
      }
      dateButton.type = 'button';
      dateButton.setAttribute('aria-label', 'Välj datum');
      dateButton.addEventListener('click', (e) => {
        e.stopPropagation();
        state.calendarOpen = !state.calendarOpen;
        if (state.calendarOpen) state.settingsOpen = false;
        const baseDate = parseDateString(state.selectedDate) || new Date();
        state.calendarMonth = startOfMonth(baseDate);
        render();
      });
      dateActions.appendChild(dateButton);

      if (state.selectedDate) {
        const clearBtn = createEl('button', 'skyview-date-clear', '✕');
        clearBtn.type = 'button';
        clearBtn.title = 'Rensa datum';
        clearBtn.setAttribute('aria-label', 'Rensa datum');
        clearBtn.addEventListener('click', () => {
          state.selectedDate = '';
          state.calendarOpen = false;
          state.calendarMonth = startOfMonth(new Date());
          state.loading = true;
          scheduleRefresh();
          render();
          fetchLoads();
        });
        dateActions.appendChild(clearBtn);
      }

      if (state.calendarOpen) {
        dateActions.appendChild(renderCalendarPopup());
      }

      dateControl.appendChild(dateActions);
      toolbarLeft.appendChild(dateControl);
    }

    const tvLink = createEl('a', 'skyview-tv-link');
    tvLink.href = '/skyview-full';
    tvLink.title = 'Fullskärm';
    tvLink.setAttribute('aria-label', 'Visa i fullskärm');
    tvLink.appendChild(createTvIcon());
    toolbarLeft.appendChild(tvLink);

    const dateText = state.clock.toLocaleDateString('en-US', {
      month: 'long',
      day: 'numeric',
      year: 'numeric',
    });
    const clockText = `${String(state.clock.getHours()).padStart(2, '0')}:${String(state.clock.getMinutes()).padStart(2, '0')}`;

    toolbar.appendChild(toolbarLeft);
    toolbar.appendChild(toolbarRight);
    root.appendChild(toolbar);

    if (state.offlineMode) {
      root.appendChild(createEl('div', 'skyview-offline-bar', 'Offline \u2014 visar cachad data'));
    }

    if (state.error) {
      root.appendChild(createEl('div', 'skyview-notice skyview-notice-error', state.error));
      renderOverlays();
      return;
    }

    if (state.loading) {
      root.appendChild(createEl('div', 'skyview-notice', 'Laddar...'));
      renderOverlays();
      return;
    }

    if (!state.loads.length) {
      if (state.jumpQueueCount !== null) {
        const row = createEl('div', 'skyview-next-crew-row');
        row.appendChild(createEl('span', 'skyview-next-crew-item skyview-clock', `${dateText} ${clockText}`));
        const queueBtn = createEl('button', 'skyview-next-crew-item skyview-next-crew-item--right skyview-queue-badge');
        queueBtn.type = 'button';
        queueBtn.textContent = `${state.jumpQueueCount} i kön`;
        queueBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          state.queueModalOpen = true;
          fetchJumpQueue();
        });
        row.appendChild(queueBtn);
        root.appendChild(row);
      }
      root.appendChild(createEl('div', 'skyview-notice', 'Inga lyft hittades just nu.'));
      renderOverlays();
      return;
    }

    const isSelectedDateToday = state.selectedDate === '' || state.selectedDate === todayDate();
    const hasDateSelected = state.selectedDate !== '';
    const nextLoad = getNextUpcomingLoad(state.loads);
    const nextLoadId = nextLoad?.id || null;
    const crewSource = nextLoad || state.loads[state.loads.length - 1];
    const nextPilot = formatDisplayName(String(crewSource?.pilot || '').trim(), state.showQuotedNameParts);
    const nextJumpLeader = formatDisplayName(String(crewSource?.jumpLeader || '').trim(), state.showQuotedNameParts);
    const hasNextCrew = nextPilot !== '' || nextJumpLeader !== '' || state.jumpQueueCount !== null;

    const alwaysShowCrew = true;
    if (hasNextCrew || alwaysShowCrew) {
      const row = createEl('div', 'skyview-next-crew-row');
      row.appendChild(createEl('span', 'skyview-next-crew-item skyview-clock', `${dateText} ${clockText}`));
      if (nextPilot) row.appendChild(createEl('span', 'skyview-next-crew-item', `Pilot: ${nextPilot}`));
      if (nextJumpLeader) row.appendChild(createEl('span', 'skyview-next-crew-item', `Hoppledare: ${nextJumpLeader}`));
      if (state.jumpQueueCount !== null) {
        const queueBtn = createEl('button', 'skyview-next-crew-item skyview-next-crew-item--right skyview-queue-badge');
        queueBtn.type = 'button';
        queueBtn.textContent = `${state.jumpQueueCount} i kön`;
        queueBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          state.queueModalOpen = true;
          fetchJumpQueue();
        });
        row.appendChild(queueBtn);
      }
      root.appendChild(row);
    }

    // Detect when message disappears → fade it out.
    if (state.prevMessage && !state.message && !state.fadingOutMessage) {
      state.fadingOutMessage = state.prevMessage;
      state.prevMessage = '';
      setTimeout(() => {
        state.fadingOutMessage = '';
        render();
      }, 700);
    }

    const effectiveMessage = state.message || state.fadingOutMessage;
    if (effectiveMessage) {
      const isMsgFadingOut = Boolean(state.fadingOutMessage && !state.message);
      const msgIsNew = !isMsgFadingOut && effectiveMessage !== state.prevMessage;
      const msgCls = isMsgFadingOut
        ? 'skyview-message-row skyview-message-row--fading-out'
        : msgIsNew
          ? 'skyview-message-row skyview-message-row--new'
          : 'skyview-message-row';
      if (!isMsgFadingOut) state.prevMessage = effectiveMessage;
      const msgRow = createEl('div', msgCls);
      effectiveMessage.split(';').forEach((part, i) => {
        if (i > 0) msgRow.appendChild(document.createElement('br'));
        msgRow.appendChild(document.createTextNode(part.trim()));
      });
      root.appendChild(msgRow);
    }

    const board = createEl('div', 'skyview-board');
    let newIndex = 0;
    const visibleLoads = state.maxLoads > 0 ? state.loads.slice(0, state.maxLoads) : state.loads;

    // Detect comments that just disappeared and animate them out.
    // Also detect comments that just appeared or changed (for fade-in).
    const justFaded = new Map();
    const newCommentIds = new Set();
    visibleLoads.forEach((load) => {
      const prev = state.prevComments[load.id] || '';
      if (prev && !load.comment) justFaded.set(load.id, prev);
      if (load.comment && load.comment !== prev) newCommentIds.add(load.id);
      state.prevComments[load.id] = load.comment || '';
    });
    if (justFaded.size > 0) {
      justFaded.forEach((v, k) => state.fadingOutComments.set(k, v));
      setTimeout(() => {
        justFaded.forEach((_, k) => state.fadingOutComments.delete(k));
        render();
      }, 700);
    }

    visibleLoads.forEach((load) => {
      const wrap = createEl('div', 'skyview-load-sortable');
      wrap.dataset.loadId = String(load.id || '');
      const isNew = state.newLoadIds.has(load.id);
      if (state.firstRender || isNew) {
        wrap.classList.add('skyview-load-sortable--animate');
        wrap.style.setProperty('--card-index', newIndex++);
      }
      if (isNew) {
        wrap.classList.add('skyview-load-sortable--new');
      }
      const fadingComment = state.fadingOutComments.get(load.id) || '';
      const commentIsNew = newCommentIds.has(load.id);
      wrap.appendChild(renderLoadCard(load, isSelectedDateToday, load.id === nextLoadId, state, fadingComment, commentIsNew, hasDateSelected));
      board.appendChild(wrap);
    });
    root.appendChild(board);

    applyPendingInsertTransition(board);

    state.firstRender = false;
    if (state.newLoadIds.size > 0) {
      state.newLoadIds = new Set();
    }

    renderOverlays();
  }

  state.render = render;

  state.clockTimer = setInterval(() => {
    state.clock = new Date();
    if (!state.queueModalOpen && !state.settingsOpen) {
      render();
    }
  }, 30000);

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) {
      return;
    }

    let didChange = false;

    if (state.calendarOpen && !(root.contains(target) && target.closest('.skyview-date-actions'))) {
      state.calendarOpen = false;
      didChange = true;
    }

    if (state.settingsOpen && root.contains(target) && !target.closest('.skyview-settings-panel') && !target.closest('.skyview-settings-wrapper')) {
      state.settingsOpen = false;
      didChange = true;
    }

    if (state.queueModalOpen && root.contains(target) && !target.closest('.skyview-queue-modal') && !target.closest('.skyview-queue-badge')) {
      state.queueModalOpen = false;
      didChange = true;
    }

    if (didChange) {
      render();
    }
  });

  scheduleRefresh();
  render();
  fetchLoads();

  // Pull-to-refresh for standalone home-screen web app
  if (window.navigator.standalone) {
    var ptrStartY = 0;
    var ptrRefreshing = false;
    var PTR_THRESHOLD = 80;

    document.addEventListener('touchstart', function (e) {
      if (ptrRefreshing) return;
      var scrollTop = window.scrollY || document.documentElement.scrollTop || 0;
      if (scrollTop <= 0) {
        ptrStartY = e.touches[0].clientY;
      }
    }, { passive: true });

    document.addEventListener('touchend', function (e) {
      if (ptrRefreshing || state.settingsOpen || state.queueModalOpen) return;
      var dist = e.changedTouches[0].clientY - ptrStartY;
      var scrollTop = window.scrollY || document.documentElement.scrollTop || 0;
      if (scrollTop <= 0 && dist >= PTR_THRESHOLD) {
        location.reload();
      }
    }, { passive: true });
  }

  // Sync push subscription on load if notifications are enabled.
  if (state.notifyNewLoad || state.notifyNewJumper || state.notifyNewMessage || state.notifyNewQueueJumper) {
    syncPushSubscription(vapidPublicKey, pushEndpoint, state);
  }
}

document.querySelectorAll('[data-skyview-endpoint]').forEach((el) => {
  mountSkyview(el);
});