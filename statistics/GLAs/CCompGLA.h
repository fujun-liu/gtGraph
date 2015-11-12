#ifndef _UNION_FIND_GLA_H_
#define _UNION_FIND_GLA_H_
#include <unordered_map>
#include <set>
#include <cstdint>
#include "UnionFindMap.h"

//#define DEBUG

#ifdef DEBUG
#include <iostream>
#endif
// node id are assumed to be non-negative uint64_t int
//template<class uint64_t> 
class CCompGLA{
	private:
		//std::unordered_map<uint64_t, uint64_t> compIDLocal, compIDGlobal;
		UnionFindMap localUF, globalUF;
		std::unordered_map<uint64_t, uint64_t>::iterator OutputIterator, EndOfOutput;
		bool localFinalized = false;
	public:
		// constructor did nothing
		CCompGLA(){}

		// put merge small tree into higher tree
		// if disjoint, merge and return false
		void AddItem(uint64_t i, uint64_t j){
			localUF.Union(i, j);
		}
        

		// finalize local state
		void FinalizeLocalState(){
			if (!localFinalized){
				localUF.FinalizeRoot();
				localFinalized = true;
			}

			#ifdef DEBUG
			// count number of components
			std::unordered_map<uint64_t, std::set<uint64_t>> local_comps;
			std::unordered_map<uint64_t, uint64_t>& compIDLocal = localUF.GetUF();

			for (auto const& entry:compIDLocal){
				uint64_t comp_id = entry.second;
				if (local_comps.find(comp_id) != local_comps.end()){
					local_comps[comp_id].insert(entry.first);
				}else{
					std::set<uint64_t> tmp; tmp.insert(entry.first);
					local_comps[comp_id] = tmp;
				}
			}

			std::cout << "There are " << local_comps.size() << " components in the partition \n";
			#endif
		}
		

		void AddState(CCompGLA& other){
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

		void Finalize(){
			OutputIterator = localUF.GetUF().begin();
			EndOfOutput = localUF.GetUF().end();
		}
		
		bool GetNextResult(uint64_t& nodeID, uint64_t& compID){
			if (OutputIterator != EndOfOutput){
				nodeID = OutputIterator->first;
				compID = OutputIterator->second;
				++ OutputIterator;
				return true;
			}else{
				return false;
			}
		}
    	
    	~CCompGLA(){}

};

#endif
