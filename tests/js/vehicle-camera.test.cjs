const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const adapter = fs.readFileSync(path.join(__dirname, '../../public/js/vehicle-visit-camera.js'), 'utf8');

async function initialize({ available, configured, denied = [] }) {
    const roles = ['licencia', 'placa', 'visitante'];
    const dataset = {};
    roles.forEach((role, index) => {
        const suffix = role[0].toUpperCase() + role.slice(1);
        dataset['camera' + suffix] = configured[index];
        dataset['photo' + suffix] = 'true';
    });
    const videos = roles.map(() => ({ srcObject: null, async play() {} }));
    const opened = [], stopped = [], listeners = new Map();
    const window = { addEventListener: (name, callback) => listeners.set(name, callback) };
    const navigator = { mediaDevices: {
        enumerateDevices: async () => available.map(id => ({ kind: 'videoinput', deviceId: id, label: 'Camera ' + id })),
        getUserMedia: async constraints => {
            const id = constraints.video?.deviceId?.exact || 'permission';
            opened.push(id);
            if (denied.includes(id)) throw new Error('Camera unavailable');
            return { getTracks: () => [{ stop: () => stopped.push(id) }] };
        },
    } };
    const document = { getElementById: id => id.startsWith('video') ? videos[Number(id.slice(-1)) - 1] : {} };
    vm.runInNewContext(adapter, { window, navigator, document });
    window.VehicleVisitCamera.initialize({ querySelector: () => ({ dataset }) }, {}, {});
    await new Promise(resolve => setImmediate(resolve));
    return { videos, opened: opened.filter(id => id !== 'permission'), stopped, leave: () => listeners.get('pagehide')() };
}

test('a shared saved ID from another origin still uses one stream with several devices available', async () => {
    const state = await initialize({ available: ['droidcam', 'webcam', 'virtual'], configured: ['old-id', 'old-id', 'old-id'] });
    assert.deepEqual(state.opened, ['droidcam']);
    assert.ok(state.videos.every(video => video.srcObject === state.videos[0].srcObject));
    assert.ok(state.videos[0].srcObject);
    state.leave();
    assert.deepEqual(state.stopped, ['permission', 'droidcam']);
});

test('unavailable alternate cameras do not leave two shared previews empty', async () => {
    const state = await initialize({ available: ['droidcam', 'webcam', 'virtual'], configured: ['old-id', 'old-id', 'old-id'], denied: ['webcam', 'virtual'] });
    assert.ok(state.videos.every(video => video.srcObject));
    assert.deepEqual(state.opened, ['droidcam']);
});

test('distinct stale assignments stay distinct, while a repeated stale assignment stays shared', async () => {
    const state = await initialize({ available: ['a', 'b', 'c'], configured: ['old-a', 'old-b', 'old-b'] });
    assert.deepEqual(state.opened, ['a', 'b']);
    assert.notEqual(state.videos[0].srcObject, state.videos[1].srcObject);
    assert.equal(state.videos[1].srcObject, state.videos[2].srcObject);
});

test('valid shared IDs and labels open the same camera once', async () => {
    for (const id of ['a', 'Camera a']) {
        const state = await initialize({ available: ['a', 'b'], configured: [id, id, id] });
        assert.deepEqual(state.opened, ['a']);
        assert.ok(state.videos.every(video => video.srcObject === state.videos[0].srcObject));
    }
});

test('three available configured devices and one-device fallback keep working', async () => {
    const distinct = await initialize({ available: ['a', 'b', 'c'], configured: ['a', 'b', 'c'] });
    assert.deepEqual(distinct.opened, ['a', 'b', 'c']);
    assert.equal(new Set(distinct.videos.map(video => video.srcObject)).size, 3);
    const single = await initialize({ available: ['a'], configured: ['old-a', 'old-b', 'old-c'] });
    assert.deepEqual(single.opened, ['a']);
    assert.ok(single.videos.every(video => video.srcObject === single.videos[0].srcObject));
});

test('a failed shared fallback is attempted once', async () => {
    const state = await initialize({ available: ['a', 'b'], configured: ['old-id', 'old-id', 'old-id'], denied: ['a'] });
    assert.deepEqual(state.opened, ['a']);
    assert.ok(state.videos.every(video => video.srcObject === null));
});
