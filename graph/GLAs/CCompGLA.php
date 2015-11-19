<?
function ConnectedComponents(array $t_args, array $inputs, array $outputs)
{
    // Class name is randomly generated
    $className = generate_name("CCompGLA");

    // Processing of inputs.
    grokit_assert(count($inputs) == 2, 'Connected Components: 2 inputs expected');
    $inputs_ = array_combine(['src', 'dst'], $inputs);

    // Setting output type
    $outType = lookupType('int');
    $outputs_ = ['node' => $outType, 'component' => $outType];
    $outputs = array_combine(array_keys($outputs), $outputs_);

    $sys_headers = ['unordered_map', 'set', 'vector'];
    $user_headers = [];
    $lib_headers = ['UnionFindMap.h'];
?>

using namespace std;

class <?=$className?>;

class <?=$className?> {
 private:
  // union-find map data structure, which contains nodeID->compID information
  UnionFindMap localUF, globalUF;
  std::unordered_map<uint64_t, uint64_t>::iterator OutputIterator, EndOfOutput;
  bool localFinalized = false;
 public:
  <?=$className?>() {}

  void AddItem(<?=const_typed_ref_args($inputs_)?>) {
    uint64_t src_ = Hash(src);
    uint64_t dst_ = Hash(dst);
  
    localUF.Union(src_, dst_);
  }

  void AddState(<?=$className?> &other) {
      FinalizeLocalState();
      other.FinalizeLocalState();
      std::unordered_map<uint64_t, uint64_t>& compIDLocal = localUF.GetUF();
      std::unordered_map<uint64_t, uint64_t>& otherState = other.localUF.GetUF();

      for(auto const& entry:otherState){
        if (compIDLocal.find(entry.first) != compIDLocal.end()
            && compIDLocal[entry.first] != entry.second){ // merge needed
          globalUF.Union(compIDLocal[entry.first], entry.second);
        }else{
          compIDLocal[entry.first] = entry.second;
        }
      }

      // merge local and global
      globalUF.FinalizeRoot();
      std::unordered_map<uint64_t, uint64_t>& compIDGlobal = globalUF.GetUF();
      for (auto& p:compIDLocal){
        if (compIDGlobal.find(p.second) != compIDGlobal.end()){
          p.second = compIDGlobal[p.second];
        }
      }
      globalUF.Clear();
  }

  void FinalizeLocalState(){
	if (!localFinalized){
		localUF.FinalizeRoot();
		localFinalized = true;
	}
  }

  void Finalize(){
      OutputIterator = localUF.GetUF().begin();
      EndOfOutput = localUF.GetUF().end();
  }

  bool GetNextResult(<?=typed_ref_args($outputs_)?>) {
      if (OutputIterator != EndOfOutput){
        node = OutputIterator->first;
        component = OutputIterator->second;
        ++ OutputIterator;
        return true;
      }else{
        return false;
      }
 }
};

<?
    return [
        'kind'           => 'GLA',
        'name'           => $className,
        'system_headers' => $sys_headers,
        'user_headers'   => $user_headers,
        'lib_headers'    => $lib_headers,
        'input'          => $inputs,
        'output'         => $outputs,
        'result_type'    => 'multi',
    ];
}
?>
