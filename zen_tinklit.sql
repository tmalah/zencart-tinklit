CREATE TABLE IF NOT EXISTS `tinklit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `guid` varchar(64) NOT NULL,
  `status` varchar(64) NOT NULL,
  `btc_price` float NOT NULL,
  `invoice_time` varchar(64) NOT NULL,
  `payment_confidence` varchar(64) NOT NULL,
  `time_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
