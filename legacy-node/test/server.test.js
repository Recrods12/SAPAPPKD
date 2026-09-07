const test = require('node:test');
const assert = require('node:assert/strict');
const { haversine, jakartaParts } = require('../server');

test('haversine returns zero for identical coordinates', () => assert.equal(haversine(-6.1,106.7,-6.1,106.7),0));
test('haversine calculates realistic distance', () => {
  const distance=haversine(-6.108139,106.707762,-6.108049,106.707762);
  assert.ok(distance>9 && distance<11);
});
test('Jakarta date uses YYYY-MM-DD format', () => assert.match(jakartaParts().date,/^\d{4}-\d{2}-\d{2}$/));
