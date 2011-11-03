<?php
namespace org\rhaco\net\xml\atom;
/**
 * Atom化するためのインタフェース
 * @author tokushima
 */
interface AtomInterface{
	public function atom_id();
	public function atom_title();
	public function atom_published();
	public function atom_updated();
	public function atom_issued();
	public function atom_content();
	public function atom_summary();
	public function atom_href();
	public function atom_author();	
}
