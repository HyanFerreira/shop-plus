import test from 'node:test';
import assert from 'node:assert/strict';

globalThis.document = { addEventListener() {} };
const { cartQuantityUpdates } = await import('./cart-quantity.js');

test('one cart mutation runs at a time, preserving the value sent with its item', async () => {
    const calls = [];
    let release;
    const state = cartQuantityUpdates();
    state.$wire = {
        quantities: { 7: 2, 8: 1 },
        set: (...args) => calls.push(['set', ...args]),
        updateItem: id => { calls.push(['update', id]); return new Promise(resolve => release = resolve); },
        removeItem: id => calls.push(['remove', id]),
        clearCart: () => calls.push(['clear']),
    };
    const request = state.changeQuantity(7, 1, 5);
    assert.equal(state.busy, true);
    await state.changeQuantity(8, 1, 5);
    await state.removeItem(8);
    await state.clearCart();
    assert.deepEqual(calls, [['set', 'quantities.7', 3, false], ['update', 7]]);
    release(true); await request;
    assert.equal(state.busy, false);
    await state.removeItem(8);
    assert.deepEqual(calls.at(-1), ['remove', 8]);
});

test('manual zero is submitted once for existing removal semantics and bounds stay enforced', async () => {
    const calls = [];
    const state = cartQuantityUpdates();
    state.$wire = { quantities: { 1: 1 }, set: (...args) => calls.push(args), updateItem: id => { calls.push(id); return true; } };
    await state.changeQuantity(1, -1, 3);
    assert.deepEqual(calls, []);
    state.$wire.quantities[1] = 3;
    await state.changeQuantity(1, 1, 3);
    assert.deepEqual(calls, []);
    await state.saveQuantity(1, '0');
    assert.deepEqual(calls, [['quantities.1', '0', false], 1]);
});

test('failed requests release the busy guard and provide retry feedback', async () => {
    const state = cartQuantityUpdates();
    state.$wire = { set() {}, updateItem: () => Promise.reject(new Error('offline')) };
    await state.saveQuantity(1, '2');
    assert.equal(state.busy, false);
    assert.match(state.message, /Tente novamente/);
});

test('a typed draft followed by increment sends the final quantity once', async () => {
    const state = cartQuantityUpdates(), calls=[];
    state.$wire = { quantities: {1:'3'}, set: (...args)=>calls.push(args), updateItem: ()=>true };
    state.stageQuantity(1, '3');
    await state.changeQuantity(1, 1, 5);
    assert.deepEqual(calls, [['quantities.1',4,false]]);
    assert.equal(state.draft, null);
});

test('navigation waits for a draft save and does not proceed after validation fails', async () => {
    const state = cartQuantityUpdates(), events=[];
    let release;
    state.$wire={set(){},updateItem:()=>new Promise(r=>release=r)};
    state.redirect=url=>events.push(url);
    state.stageQuantity(1,'3');
    const navigation=state.navigate('/checkout');
    assert.deepEqual(events,[]);
    release(true);await navigation;
    assert.deepEqual(events,['/checkout']);
    state.$wire.updateItem=()=>false;
    state.stageQuantity(1,'999');
    await state.navigate('/checkout');
    assert.deepEqual(events,['/checkout']);
    assert.notEqual(state.draft,null);
});

test('remove and clear discard drafts for items being removed without quantity requests', async () => {
    const state=cartQuantityUpdates(),calls=[];
    state.$wire={set(){calls.push('set');},updateItem(){calls.push('update');return true;},removeItem:id=>calls.push(id),clearCart:()=>calls.push('clear')};
    state.stageQuantity(1,'3');await state.removeItem(1);
    state.stageQuantity(2,'4');await state.clearCart();
    assert.deepEqual(calls,[1,'clear']);
});
