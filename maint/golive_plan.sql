DELETE duplicate_bar
FROM marketdata_history_bars duplicate_bar
JOIN marketdata_history_bars kept_bar
  ON kept_bar.conid = duplicate_bar.conid
 AND kept_bar.sec_type = duplicate_bar.sec_type
 AND COALESCE(kept_bar.exchange, '') = COALESCE(duplicate_bar.exchange, '')
 AND kept_bar.bar_value = duplicate_bar.bar_value
 AND kept_bar.outside_rth = duplicate_bar.outside_rth
 AND kept_bar.source_value = duplicate_bar.source_value
 AND kept_bar.bar_time = duplicate_bar.bar_time
 AND (
      kept_bar.fetched_at > duplicate_bar.fetched_at
      OR (kept_bar.fetched_at = duplicate_bar.fetched_at AND kept_bar.id > duplicate_bar.id)
 )
WHERE duplicate_bar.id <> kept_bar.id;

ALTER TABLE marketdata_history_bars
  ADD COLUMN exchange_key varchar(32)
    GENERATED ALWAYS AS (COALESCE(exchange, '')) STORED
    AFTER exchange,
  ADD UNIQUE KEY marketdata_history_bar_identity_unique
    (conid, sec_type, exchange_key, bar_value, outside_rth, source_value, bar_time),
  ADD KEY marketdata_history_bar_identity_lookup
    (conid, sec_type, exchange_key, bar_value, outside_rth, source_value, bar_time, fetched_at);
